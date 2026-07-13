<?php

namespace Tests\Feature;

use App\Models\PilotInvite;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_log_commercial_action_on_pilot_invite(): void
    {
        $user = $this->createOnboardedUser();

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Actiune Test SRL',
            'contact_email' => 'contact@actiunetest.ro',
            'status' => 'contacted',
            'invited_at' => now(),
            'last_contacted_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($user)
            ->post("/pilot-invites/{$invite->id}/actions", [
                'action_type' => 'demo',
                'notes' => 'Demo prezentat, urmeaza oferta.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('commercial_actions', [
            'tenant_id' => 1,
            'pilot_invite_id' => $invite->id,
            'actor_id' => $user->id,
            'action_type' => 'demo',
            'notes' => 'Demo prezentat, urmeaza oferta.',
        ]);

        $invite->refresh();
        $this->assertTrue($invite->last_contacted_at->greaterThan(now()->subMinute()));
    }

    public function test_commercial_action_requires_a_valid_type(): void
    {
        $user = $this->createOnboardedUser();

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Validare Test SRL',
            'contact_email' => 'contact@validaretest.ro',
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post("/pilot-invites/{$invite->id}/actions", [
                'action_type' => 'nu_exista',
            ]);

        $response->assertSessionHasErrors('action_type');
        $this->assertDatabaseCount('commercial_actions', 0);
    }

    public function test_user_cannot_log_action_on_other_tenant_invite(): void
    {
        Tenant::create([
            'id' => 2,
            'name' => 'Tenant intrus',
            'slug' => 'tenant-intrus',
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ]);

        $user = $this->createOnboardedUser();

        $otherOwner = User::factory()->create(['tenant_id' => 2, 'current_tenant_id' => 2]);
        $otherInvite = PilotInvite::create([
            'tenant_id' => 2,
            'owner_id' => $otherOwner->id,
            'company_name' => 'Alt Tenant SRL',
            'contact_email' => 'contact@alttenant.ro',
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post("/pilot-invites/{$otherInvite->id}/actions", [
                'action_type' => 'apel',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('commercial_actions', 0);
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
