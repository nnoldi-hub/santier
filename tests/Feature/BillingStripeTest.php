<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\PricingPlan;
use App\Support\StripeSubscriptionSync;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BillingStripeTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_for_stripe_price_resolves_configured_plan(): void
    {
        Config::set('pricing.plans.starter.stripe_price_id', 'price_test_starter');
        Config::set('pricing.plans.pro.stripe_price_id', 'price_test_pro');

        $this->assertSame('starter', PricingPlan::planForStripePrice('price_test_starter'));
        $this->assertSame('pro', PricingPlan::planForStripePrice('price_test_pro'));
        $this->assertNull(PricingPlan::planForStripePrice('price_unknown'));
    }

    public function test_price_id_for_plan_resolves_monthly_and_yearly(): void
    {
        Config::set('pricing.plans.starter.stripe_price_id', 'price_test_starter');
        Config::set('pricing.plans.starter.stripe_price_id_yearly', 'price_test_starter_yearly');

        $this->assertSame('price_test_starter', PricingPlan::priceIdForPlan('starter'));
        $this->assertSame('price_test_starter', PricingPlan::priceIdForPlan('starter', 'monthly'));
        $this->assertSame('price_test_starter_yearly', PricingPlan::priceIdForPlan('starter', 'yearly'));
    }

    public function test_plan_for_stripe_price_resolves_yearly_price(): void
    {
        Config::set('pricing.plans.pro.stripe_price_id', 'price_test_pro');
        Config::set('pricing.plans.pro.stripe_price_id_yearly', 'price_test_pro_yearly');

        $this->assertSame('pro', PricingPlan::planForStripePrice('price_test_pro_yearly'));
    }

    public function test_interval_for_stripe_price_detects_yearly(): void
    {
        Config::set('pricing.plans.pro.stripe_price_id', 'price_test_pro');
        Config::set('pricing.plans.pro.stripe_price_id_yearly', 'price_test_pro_yearly');

        $this->assertSame('yearly', PricingPlan::intervalForStripePrice('price_test_pro_yearly'));
        $this->assertSame('monthly', PricingPlan::intervalForStripePrice('price_test_pro'));
    }

    public function test_subscription_updated_webhook_syncs_tenant_plan(): void
    {
        Config::set('pricing.plans.pro.stripe_price_id', 'price_test_pro');
        $tenant = $this->createTenantWithStripeId('free');

        StripeSubscriptionSync::applyUpdated($this->subscriptionPayload('active', 'price_test_pro'));

        $this->assertSame('pro', $tenant->fresh()->billing_plan);
    }

    public function test_subscription_updated_webhook_ignores_inactive_status(): void
    {
        Config::set('pricing.plans.pro.stripe_price_id', 'price_test_pro');
        $tenant = $this->createTenantWithStripeId('starter');

        StripeSubscriptionSync::applyUpdated($this->subscriptionPayload('incomplete', 'price_test_pro'));

        $this->assertSame('starter', $tenant->fresh()->billing_plan);
    }

    public function test_subscription_updated_webhook_ignores_unknown_customer(): void
    {
        Config::set('pricing.plans.pro.stripe_price_id', 'price_test_pro');
        $this->createTenantWithStripeId('starter');

        // No exception, no matching tenant - should just be a no-op.
        StripeSubscriptionSync::applyUpdated([
            'data' => ['object' => [
                'customer' => 'cus_unknown',
                'status' => 'active',
                'items' => ['data' => [['price' => ['id' => 'price_test_pro']]]],
            ]],
        ]);

        $this->assertDatabaseHas('tenants', ['stripe_id' => 'cus_test123', 'billing_plan' => 'starter']);
    }

    public function test_subscription_deleted_webhook_resets_tenant_to_free(): void
    {
        $tenant = $this->createTenantWithStripeId('pro');

        StripeSubscriptionSync::applyDeleted([
            'data' => ['object' => ['customer' => 'cus_test123']],
        ]);

        $this->assertSame('free', $tenant->fresh()->billing_plan);
    }

    public function test_checkout_rejects_unknown_plan(): void
    {
        $user = $this->createOnboardedUser('free');

        $this->actingAs($user)->get('/billing/checkout/free')->assertStatus(404);
    }

    public function test_checkout_rejects_invalid_interval(): void
    {
        $user = $this->createOnboardedUser('free');

        $this->actingAs($user)->get('/billing/checkout/starter?interval=biweekly')->assertStatus(422);
    }

    public function test_swap_requires_existing_subscription(): void
    {
        $user = $this->createOnboardedUser('free');

        $this->actingAs($user)->patch('/billing/swap', ['plan' => 'pro'])->assertStatus(422);
    }

    public function test_swap_rejects_invalid_interval(): void
    {
        $user = $this->createOnboardedUser('free');

        $this->actingAs($user)->patch('/billing/swap', ['plan' => 'pro', 'interval' => 'biweekly'])->assertStatus(422);
    }

    public function test_cancel_requires_existing_subscription(): void
    {
        $user = $this->createOnboardedUser('free');

        $this->actingAs($user)->patch('/billing/cancel')->assertStatus(422);
    }

    public function test_resume_requires_grace_period_subscription(): void
    {
        $user = $this->createOnboardedUser('free');

        $this->actingAs($user)->patch('/billing/resume')->assertStatus(422);
    }

    public function test_portal_requires_stripe_customer(): void
    {
        $user = $this->createOnboardedUser('free');

        $this->actingAs($user)->get('/billing/portal')->assertStatus(422);
    }

    private function subscriptionPayload(string $status, string $priceId): array
    {
        return [
            'data' => ['object' => [
                'customer' => 'cus_test123',
                'status' => $status,
                'items' => ['data' => [['price' => ['id' => $priceId]]]],
            ]],
        ];
    }

    private function createTenantWithStripeId(string $plan): Tenant
    {
        return Tenant::create([
            'id' => 1,
            'name' => 'Tenant Test',
            'slug' => 'tenant-test',
            'billing_plan' => $plan,
            'status' => 'active',
            'module_flags' => [],
            'stripe_id' => 'cus_test123',
        ]);
    }

    private function createOnboardedUser(string $plan): User
    {
        $user = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => $plan,
        ]);

        $this->seed(IamSeeder::class);
        Tenant::find(1)?->update(['billing_plan' => $plan]);

        return $user->fresh();
    }
}
