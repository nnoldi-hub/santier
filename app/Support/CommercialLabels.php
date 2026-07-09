<?php

namespace App\Support;

class CommercialLabels
{
    public static function status(string $status): string
    {
        return match ($status) {
            'invited' => 'Invitat',
            'contacted' => 'Contactat',
            'demo_scheduled' => 'Demo programat',
            'trial_started' => 'Trial pornit',
            'closed_won' => 'Castigat',
            'closed_lost' => 'Pierdut',
            default => $status,
        };
    }

    public static function stage(string $stage): string
    {
        return match ($stage) {
            'prospecting' => 'Prospectare',
            'contacted' => 'Contactat',
            'follow_up' => 'Follow-up',
            'demo' => 'Demo',
            'trial' => 'Trial',
            'negotiation' => 'Negociere',
            'won' => 'Castigat',
            'lost' => 'Pierdut',
            default => $stage,
        };
    }

    public static function plan(string $plan): string
    {
        return match ($plan) {
            'starter' => 'Brand de baza',
            'pro' => 'Brand complet',
            'enterprise' => 'Enterprise',
            'free' => 'Demo',
            default => $plan,
        };
    }

    public static function risk(string $risk): string
    {
        return match ($risk) {
            'high' => 'Ridicat',
            'medium' => 'Mediu',
            'low' => 'Scazut',
            default => $risk,
        };
    }
}
