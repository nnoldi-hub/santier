<?php

namespace Tests\Feature;

use App\Models\CommercialTask;
use App\Models\PilotInvite;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilotInvitesTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarded_user_can_create_pilot_invite(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)
            ->from('/pilot-invites')
            ->post('/pilot-invites', [
                'company_name' => 'Construct Pro SRL',
                'segment' => 'renovari',
                'contact_name' => 'Mihai Ionescu',
                'contact_email' => 'mihai@constructpro.ro',
                'contact_phone' => '0722333444',
                'estimated_users' => 25,
                'customization_scope' => 'template',
                'follow_up_at' => now()->addDay()->toDateTimeString(),
                'notes' => 'Interes pentru pilot iulie.',
            ]);

        $response->assertRedirect('/pilot-invites');

        $this->assertDatabaseHas('pilot_invites', [
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Construct Pro SRL',
            'contact_email' => 'mihai@constructpro.ro',
            'status' => 'invited',
        ]);

        $invite = PilotInvite::query()->latest('id')->first();
        $this->assertNotNull($invite);

        $this->assertDatabaseHas('commercial_tasks', [
            'pilot_invite_id' => $invite->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $notification = $user->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame(OperationalReminderNotification::class, $notification->type);
        $this->assertSame('commercial_follow_up', (string) ($notification->data['event'] ?? null));
    }

    public function test_user_can_update_pilot_invite_status(): void
    {
        $user = $this->createOnboardedUser();

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Pilot Build SRL',
            'contact_email' => 'contact@pilotbuild.ro',
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        CommercialTask::create([
            'tenant_id' => 1,
            'pilot_invite_id' => $invite->id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'title' => 'Follow-up comercial: Pilot Build SRL',
            'description' => 'Task initial',
            'status' => 'todo',
            'priority' => 'medium',
            'due_at' => now()->addDay(),
            'automated' => true,
        ]);

        $response = $this->actingAs($user)
            ->patch("/pilot-invites/{$invite->id}/status", [
                'status' => 'demo_scheduled',
                'demo_scheduled_at' => now()->addDays(2)->toDateString(),
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('pilot_invites', [
            'id' => $invite->id,
            'status' => 'demo_scheduled',
        ]);

        $this->assertDatabaseHas('commercial_tasks', [
            'pilot_invite_id' => $invite->id,
            'status' => 'todo',
            'priority' => 'high',
        ]);
    }

    public function test_closed_pilot_status_cancels_open_commercial_task(): void
    {
        $user = $this->createOnboardedUser();

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Close Flow SRL',
            'contact_email' => 'close@flow.ro',
            'status' => 'trial_started',
            'commercial_stage' => 'trial',
            'invited_at' => now(),
        ]);

        $task = CommercialTask::create([
            'tenant_id' => 1,
            'pilot_invite_id' => $invite->id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'title' => 'Follow-up comercial: Close Flow SRL',
            'description' => 'Task deschis',
            'status' => 'todo',
            'priority' => 'high',
            'due_at' => now()->addDay(),
            'automated' => true,
        ]);

        $this->actingAs($user)
            ->patch("/pilot-invites/{$invite->id}/status", [
                'status' => 'closed_won',
                'commercial_stage' => 'won',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('commercial_tasks', [
            'id' => $task->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_status_filter_returns_only_matching_invites(): void
    {
        $user = $this->createOnboardedUser();

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Alpha Invite',
            'contact_email' => 'alpha@invite.ro',
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Beta Won',
            'contact_email' => 'beta@invite.ro',
            'status' => 'closed_won',
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/pilot-invites?status=closed_won');

        $response->assertStatus(200);
        $response->assertSee('Beta Won');
        $response->assertDontSee('Alpha Invite');
    }

    private function createOnboardedUser(): User
    {
        return User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);
    }
}
