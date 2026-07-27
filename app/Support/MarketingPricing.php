<?php

namespace App\Support;

class MarketingPricing
{
    /**
     * Presents config('pricing.plans') for public-facing marketing surfaces
     * (landing page, brochure PDF) - shared so a price change only needs to
     * happen in one place.
     */
    public static function plans(): array
    {
        return collect(config('pricing.plans', []))->map(function (array $plan, string $key) {
            return [
                'key' => $key,
                'name' => $plan['label'] ?? $key,
                'price' => (int) ($plan['price'] ?? 0),
                'price_yearly' => (int) ($plan['price_yearly'] ?? 0),
                'period' => $plan['billing_period'] ?? 'luna',
                'highlight' => $key === 'pro',
                'badge' => match ($key) {
                    'free' => 'Start rapid',
                    'starter' => 'Brand de baza',
                    'pro' => 'Recomandat',
                    'enterprise' => 'White-label',
                    default => 'Plan',
                },
                'cta_label' => match ($key) {
                    'free' => 'Incearca demo',
                    'starter' => 'Alege branding de baza',
                    'pro' => 'Alege brand complet',
                    'enterprise' => 'Cere oferta enterprise',
                    default => 'Alege planul',
                },
                'items' => match ($key) {
                    'free' => ['Brand standard', 'Acces demo', '1 proiect', 'Evaluare rapida'],
                    'starter' => ['Logo si date firma', 'Branding de baza', '5 proiecte', '3 utilizatori'],
                    'pro' => ['Logo + culori', 'Antet si footer', 'Template documente', '10 utilizatori'],
                    'enterprise' => ['Mai multe sabloane', 'Aprobari', 'White-label', 'Domeniu propriu'],
                    default => [],
                },
            ];
        })->values()->all();
    }
}
