<?php

namespace Tests\Feature;

use App\Models\PilotInvite;
use App\Models\User;
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
