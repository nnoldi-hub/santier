<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use App\Support\PricingPlan;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $platformSettings = AppSetting::allWithDefaults(config('platform.defaults', []));

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'notifications' => [
                'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
                'items' => $user
                    ? $user->unreadNotifications()
                        ->latest()
                        ->limit(8)
                        ->get()
                        ->map(fn ($notification) => [
                            'id' => $notification->id,
                            'type' => class_basename($notification->type),
                            'created_at' => optional($notification->created_at)->toDateTimeString(),
                            'data' => $notification->data,
                        ])
                        ->values()
                    : [],
            ],
            'platform' => [
                'appName' => $platformSettings['app_name'] ?? config('app.name'),
                'companyName' => $platformSettings['company_name'] ?? config('app.name'),
                'companyPhone' => $platformSettings['company_phone'] ?? null,
                'companyAddress' => $platformSettings['company_address'] ?? null,
                'supportEmail' => $platformSettings['support_email'] ?? null,
                'salesEmail' => $platformSettings['sales_email'] ?? null,
                'documentLogoUrl' => $platformSettings['document_logo_url'] ?? null,
                'documentBrandColor' => $platformSettings['document_brand_color'] ?? null,
                'trialDays' => (int) ($platformSettings['trial_days'] ?? 14),
                'publicSignupEnabled' => (bool) ($platformSettings['public_signup_enabled'] ?? true),
                'demoModeEnabled' => (bool) ($platformSettings['demo_mode_enabled'] ?? true),
                'isAdmin' => $user ? in_array(strtolower($user->email), array_map('strtolower', config('platform.admin_emails', [])), true) : false,
            ],
            'billing' => [
                'plan' => $user ? PricingPlan::current($user) : null,
                'planLabel' => $user ? PricingPlan::label($user) : null,
            ],
            'demoMode' => [
                'enabled' => $user?->email === config('demo.email', 'demo@santier.local'),
                'label' => 'Mod demo',
                'description' => 'Vezi doar proiectele si datele din scenariul de evaluare.',
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
