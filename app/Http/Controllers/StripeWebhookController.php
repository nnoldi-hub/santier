<?php

namespace App\Http\Controllers;

use App\Support\StripeSubscriptionSync;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Fired when a subscription is first created via Checkout - a brand new
     * subscription does NOT fire "updated", only "created", so the plan sync
     * has to be hooked here too or a tenant's first-ever paid subscription
     * never activates the right plan.
     */
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);

        StripeSubscriptionSync::applyUpdated($payload);

        return $response;
    }

    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        StripeSubscriptionSync::applyUpdated($payload);

        return $response;
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        StripeSubscriptionSync::applyDeleted($payload);

        return $response;
    }
}
