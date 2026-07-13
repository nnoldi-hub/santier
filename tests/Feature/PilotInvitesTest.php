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

    public function test_reminder_today_filter_returns_only_invites_with_follow_up_today(): void
    {
        $user = $this->createOnboardedUser();

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Today Follow Up',
            'contact_email' => 'today@invite.ro',
            'status' => 'contacted',
            'invited_at' => now(),
            'follow_up_at' => now(),
        ]);

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Next Week Follow Up',
            'contact_email' => 'nextweek@invite.ro',
            'status' => 'contacted',
            'invited_at' => now(),
            'follow_up_at' => now()->addWeek(),
        ]);

        $response = $this->actingAs($user)->get('/pilot-invites?reminder_today=1');

        $response->assertStatus(200);
        $response->assertSee('Today Follow Up');
        $response->assertDontSee('Next Week Follow Up');
    }

    public function test_no_next_step_filter_returns_only_invites_missing_next_step(): void
    {
        $user = $this->createOnboardedUser();

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Missing Next Step',
            'contact_email' => 'missing@invite.ro',
            'status' => 'invited',
            'invited_at' => now(),
            'next_step' => null,
        ]);

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Has Next Step',
            'contact_email' => 'has@invite.ro',
            'status' => 'invited',
            'invited_at' => now(),
            'next_step' => 'Suna clientul',
        ]);

        $response = $this->actingAs($user)->get('/pilot-invites?no_next_step=1');

        $response->assertStatus(200);
        $response->assertSee('Missing Next Step');
        $response->assertDontSee('Has Next Step');
    }

    public function test_stagnant_filter_returns_only_active_invites_without_recent_contact(): void
    {
        $user = $this->createOnboardedUser();

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Stagnant Lead',
            'contact_email' => 'stagnant@invite.ro',
            'status' => 'contacted',
            'invited_at' => now(),
            'last_contacted_at' => now()->subDays(20),
        ]);

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Fresh Lead',
            'contact_email' => 'fresh@invite.ro',
            'status' => 'contacted',
            'invited_at' => now(),
            'last_contacted_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get('/pilot-invites?stagnant=1');

        $response->assertStatus(200);
        $response->assertSee('Stagnant Lead');
        $response->assertDontSee('Fresh Lead');
    }

    public function test_user_can_mark_handoff_on_closed_won_invite(): void
    {
        $user = $this->createOnboardedUser();

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Handoff Ready SRL',
            'contact_email' => 'handoff@invite.ro',
            'status' => 'closed_won',
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch("/pilot-invites/{$invite->id}/handoff");

        $response->assertRedirect();

        $invite->refresh();
        $this->assertNotNull($invite->onboarding_handoff_at);
    }

    public function test_handoff_cannot_be_marked_twice(): void
    {
        $user = $this->createOnboardedUser();

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Already Handed Off SRL',
            'contact_email' => 'already@invite.ro',
            'status' => 'closed_won',
            'invited_at' => now(),
            'onboarding_handoff_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->patch("/pilot-invites/{$invite->id}/handoff");

        $response->assertStatus(422);
    }

    public function test_handoff_cannot_be_marked_unless_closed_won(): void
    {
        $user = $this->createOnboardedUser();

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Still In Progress SRL',
            'contact_email' => 'progress@invite.ro',
            'status' => 'trial_started',
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch("/pilot-invites/{$invite->id}/handoff");

        $response->assertStatus(422);
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
