<?php

namespace App\Http\Middleware;

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

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
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
