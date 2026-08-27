<?php

namespace Tests\Feature;

use App\Models\AffiliatePartner;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AffiliateAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_referral_code_is_attributed_to_new_tenant(): void
    {
        $partner = AffiliatePartner::query()->create([
            'name' => 'IBC Focus',
            'code' => 'ibcfocus',
            'email' => 'partener@example.com',
            'commission_rate' => 10,
            'active' => true,
        ]);

        $this->get('/?ref=ibcfocus');
        $this->post('/register', [
            'name' => 'Firma Referita',
            'email' => 'referita@example.com',
            'phone' => '0711222333',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'referita@example.com')->firstOrFail();
        $this->assertSame($partner->id, Tenant::query()->findOrFail($user->tenant_id)->affiliate_partner_id);
    }

    public function test_inactive_referral_code_is_not_attributed(): void
    {
        AffiliatePartner::query()->create([
            'name' => 'Partener Inactiv',
            'code' => 'inactiv',
            'active' => false,
        ]);

        $this->get('/?ref=inactiv');
        $this->post('/register', [
            'name' => 'Firma Fara Afiliat',
            'email' => 'fara-afiliat@example.com',
            'phone' => '0744555666',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'fara-afiliat@example.com')->firstOrFail();
        $this->assertNull(Tenant::query()->findOrFail($user->tenant_id)->affiliate_partner_id);
    }

    public function test_superadmin_can_view_affiliate_overview(): void
    {
        $admin = User::factory()->create([
            'email' => 'affiliate-admin@example.com',
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
        $partner = AffiliatePartner::query()->create([
            'name' => 'Partener Test',
            'code' => 'partener-test',
            'active' => true,
        ]);
        $tenant = Tenant::query()->create([
            'name' => 'Firma Atribuita',
            'slug' => 'firma-atribuita',
            'billing_plan' => 'pro',
            'status' => 'active',
            'module_flags' => [],
            'affiliate_partner_id' => $partner->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.affiliates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AffiliateOverview')
                ->where('metrics.referred_tenants', 1)
                ->where('metrics.referred_paid_tenants', 1)
                ->where('partners.0.code', fn ($value) => $value === 'partener-test')
                ->where('partners.0.tenants_count', 1)
                ->where('partners.0.paid_tenants_count', 1)
            );
    }
}
