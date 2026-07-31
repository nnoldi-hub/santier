<?php

namespace Tests\Feature;

use App\Models\PilotInvite;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommercialDashboardPilotBridgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_risk_scored_tenant_with_a_linked_pilot_invite_exposes_its_id(): void
    {
        $admin = $this->createSuperadmin('admin@modulia.ro');
        $this->neutralizeDefaultTenantRisk();
        $atRiskTenant = $this->createAtRiskTenant('Firma Cu Fisa SRL');

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'converted_tenant_id' => $atRiskTenant->id,
            'company_name' => $atRiskTenant->name,
            'contact_email' => 'firma-cu-fisa@example.com',
            'status' => 'trial_started',
            'commercial_stage' => 'trial',
            'invited_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/commercial-dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CommercialDashboard')
                ->where('riskScoredTenants.0.pilot_invite_id', $invite->id));
    }

    public function test_risk_scored_tenant_without_a_pilot_invite_exposes_null(): void
    {
        $admin = $this->createSuperadmin('admin@modulia.ro');
        $this->neutralizeDefaultTenantRisk();
        $this->createAtRiskTenant('Firma Fara Fisa SRL');

        $this->actingAs($admin)
            ->get('/admin/commercial-dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CommercialDashboard')
                ->where('riskScoredTenants.0.pilot_invite_id', null));
    }

    public function test_staff_can_create_a_pilot_invite_from_an_at_risk_tenant(): void
    {
        $admin = $this->createSuperadmin('admin@modulia.ro');
        $atRiskTenant = $this->createAtRiskTenant('Firma Noua SRL');
        $owner = User::where('tenant_id', $atRiskTenant->id)->firstOrFail();

        $response = $this->actingAs($admin)->post("/admin/tenants/{$atRiskTenant->id}/pilot-invite");

        $invite = PilotInvite::query()->where('converted_tenant_id', $atRiskTenant->id)->firstOrFail();
        $response->assertRedirect("/pilot-invites/{$invite->id}");

        $this->assertSame('Firma Noua SRL', $invite->company_name);
        $this->assertSame($owner->email, $invite->contact_email);
        $this->assertSame('trial_started', $invite->status);
    }

    public function test_creating_a_pilot_invite_from_a_tenant_is_idempotent(): void
    {
        $admin = $this->createSuperadmin('admin@modulia.ro');
        $atRiskTenant = $this->createAtRiskTenant('Firma Idempotenta SRL');

        $this->actingAs($admin)->post("/admin/tenants/{$atRiskTenant->id}/pilot-invite");
        $this->actingAs($admin)->post("/admin/tenants/{$atRiskTenant->id}/pilot-invite");

        $this->assertDatabaseCount('pilot_invites', 1);
    }

    /**
     * The seeded default tenant (id=1, from the tenants-table migration)
     * has zero memberships, which alone triggers churn_signal and makes it
     * show up in riskScoredTenants too - neutralize that so assertions
     * against "the" at-risk tenant aren't ambiguous.
     */
    private function neutralizeDefaultTenantRisk(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $filler = User::factory()->create([
                'tenant_id' => 1,
                'current_tenant_id' => 1,
                'onboarding_step' => 3,
                'onboarding_completed_at' => now(),
            ]);
            TenantUser::create([
                'tenant_id' => 1,
                'user_id' => $filler->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }
    }

    private function createAtRiskTenant(string $name): Tenant
    {
        // churn_signal alone (<=1 active membership) is enough to get a
        // non-zero risk score without needing to fabricate trial-expiry
        // or onboarding-gap conditions too.
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_tenant_id' => $tenant->id,
            'email' => \Illuminate\Support\Str::slug($name) . '@example.com',
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $tenant;
    }

    private function createSuperadmin(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }
}
