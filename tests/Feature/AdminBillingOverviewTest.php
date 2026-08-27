<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

class AdminBillingOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_subscription_overview(): void
    {
        Config::set('pricing.plans.pro.stripe_price_id', 'price_pro_monthly');
        $admin = $this->createSuperadmin();
        $tenant = Tenant::query()->findOrFail(1);
        $tenant->update(['billing_plan' => 'pro']);

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'default',
            'stripe_id' => 'sub_test_123',
            'stripe_status' => 'past_due',
            'stripe_price' => 'price_pro_monthly',
            'quantity' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.billing.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/BillingOverview')
                ->where('metrics.total', 1)
                ->where('metrics.past_due', 1)
                ->where('subscriptions.data.0.tenant_name', $tenant->name)
                ->where('subscriptions.data.0.plan', fn ($value) => $value === 'pro')
                ->where('subscriptions.data.0.interval', fn ($value) => $value === 'monthly')
                ->where('subscriptions.data.0.status', fn ($value) => $value === 'past_due')
            );
    }

    public function test_tenant_user_cannot_view_subscription_overview(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => false,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.billing.index'))
            ->assertForbidden();
    }

    private function createSuperadmin(): User
    {
        return User::factory()->create([
            'email' => 'billing-admin@example.com',
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }
}
