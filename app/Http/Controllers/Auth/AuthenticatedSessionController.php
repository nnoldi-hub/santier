<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view - or, for a device marked "Tine-ma conectat 30
     * de zile" during a past 2FA challenge, log the visitor straight in.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $trustedDevice = TwoFactorAuthenticator::resolveTrustedDevice($request);

        if ($trustedDevice && $trustedDevice->user) {
            Auth::login($trustedDevice->user);
            $request->session()->regenerate();
            $trustedDevice->update(['last_used_at' => now()]);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();
        $trustedDevice = TwoFactorAuthenticator::resolveTrustedDevice($request);
        $hasTrustedDevice = $trustedDevice && $trustedDevice->user_id === $user->id;

        if ($user->two_factor_enabled && ! $hasTrustedDevice) {
            Auth::logout();

            $request->session()->regenerate();
            $request->session()->put('two_factor.user_id', $user->id);

            TwoFactorAuthenticator::generateAndSendCode($user);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
