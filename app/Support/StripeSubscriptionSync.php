<?php

namespace App\Support;

use Laravel\Cashier\Cashier;

class StripeSubscriptionSync
{
    /**
     * Keeps Tenant.billing_plan in sync with a Stripe `customer.subscription.updated`
     * event payload - the app's plan-gating (PricingPlan/EnsurePlanFeature) reads
     * that column directly, so it must reflect whatever Stripe considers active.
     */
    public static function applyUpdated(array $payload): void
    {
        $subscription = $payload['data']['object'] ?? [];
        $tenant = Cashier::findBillable($subscription['customer'] ?? null);

        if (!$tenant) {
            return;
        }

        $status = $subscription['status'] ?? null;

        if (!in_array($status, ['active', 'trialing'], true)) {
            return;
        }

        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $plan = $priceId ? PricingPlan::planForStripePrice($priceId) : null;

        if ($plan) {
            $tenant->update(['billing_plan' => $plan]);
        }
    }

    public static function applyDeleted(array $payload): void
    {
        $tenant = Cashier::findBillable($payload['data']['object']['customer'] ?? null);
        $tenant?->update(['billing_plan' => 'free']);
    }
}
