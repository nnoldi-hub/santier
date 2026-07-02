<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use App\Support\AnalyticsTracker;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        $platformSettings = AppSetting::allWithDefaults(config('platform.defaults', []));

        abort_unless((bool) ($platformSettings['public_signup_enabled'] ?? true), 403);

        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $platformSettings = AppSetting::allWithDefaults(config('platform.defaults', []));

        abort_unless((bool) ($platformSettings['public_signup_enabled'] ?? true), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $trialDays = (int) ($platformSettings['trial_days'] ?? 14);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'billing_plan' => 'pro',
            'billing_trial_ends_at' => now()->addDays($trialDays),
        ]);

        event(new Registered($user));

        Auth::login($user);

        AnalyticsTracker::track($request, 'register_completed', [
            'billing_plan' => $user->billing_plan,
        ], oncePerUser: true);

        return redirect(route('onboarding.show', absolute: false));
    }
}
