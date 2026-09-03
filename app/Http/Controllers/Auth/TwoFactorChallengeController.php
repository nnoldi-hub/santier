<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge', [
            'maskedEmail' => $this->maskEmail($user->email),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $throttleKey = 'two-factor:' . $user->id . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Prea multe incercari. Asteapta un minut si mai incearca o data.',
            ]);
        }

        if (! TwoFactorAuthenticator::verifyCode($user, (string) $request->input('code'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'code' => 'Codul introdus este incorect sau a expirat.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->forget('two_factor.user_id');

        Auth::login($user);
        $request->session()->regenerate();

        if ($request->boolean('remember_device')) {
            Cookie::queue(TwoFactorAuthenticator::rememberDevice($request, $user));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $throttleKey = 'two-factor-resend:' . $user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            return back()->with('error', 'Poti retrimite codul o data pe minut.');
        }

        RateLimiter::hit($throttleKey, 60);

        TwoFactorAuthenticator::generateAndSendCode($user);

        return back()->with('success', 'Un cod nou a fost trimis pe email.');
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('two_factor.user_id');

        if (! $userId) {
            return null;
        }

        return User::find($userId);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if (strlen($local) <= 2) {
            $maskedLocal = str_repeat('*', strlen($local));
        } else {
            $maskedLocal = $local[0] . str_repeat('*', strlen($local) - 2) . $local[-1];
        }

        return $maskedLocal . '@' . $domain;
    }
}
