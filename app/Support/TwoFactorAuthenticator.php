<?php

namespace App\Support;

use App\Mail\TwoFactorCodeMail;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as CookieValueObject;

class TwoFactorAuthenticator
{
    public const COOKIE_NAME = 'modulia_trusted_device';

    private const CODE_TTL_MINUTES = 10;

    private const TRUSTED_DEVICE_TTL_DAYS = 30;

    public static function generateAndSendCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'two_factor_code_hash' => Hash::make($code),
            'two_factor_code_expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ])->save();

        Mail::to($user->email)->send(new TwoFactorCodeMail($user->name, $code, self::CODE_TTL_MINUTES));
    }

    public static function verifyCode(User $user, string $code): bool
    {
        if (
            ! $user->two_factor_code_hash
            || ! $user->two_factor_code_expires_at
            || $user->two_factor_code_expires_at->isPast()
            || ! Hash::check($code, $user->two_factor_code_hash)
        ) {
            return false;
        }

        $user->forceFill([
            'two_factor_code_hash' => null,
            'two_factor_code_expires_at' => null,
        ])->save();

        return true;
    }

    /**
     * Resolves the trusted-device cookie to its record (and owning user), with
     * no user known in advance - used both to auto-login a returning visitor
     * on the bare login page, and to skip the 2FA code once credentials check out.
     */
    public static function resolveTrustedDevice(Request $request): ?TrustedDevice
    {
        $token = $request->cookie(self::COOKIE_NAME);

        if (! $token) {
            return null;
        }

        return TrustedDevice::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();
    }

    public static function rememberDevice(Request $request, User $user): CookieValueObject
    {
        $token = Str::random(64);
        $expiresAt = now()->addDays(self::TRUSTED_DEVICE_TTL_DAYS);

        $user->trustedDevices()->create([
            'token_hash' => hash('sha256', $token),
            'user_agent' => (string) $request->userAgent(),
            'ip_address' => $request->ip(),
            'expires_at' => $expiresAt,
        ]);

        return Cookie::make(
            self::COOKIE_NAME,
            $token,
            self::TRUSTED_DEVICE_TTL_DAYS * 24 * 60,
            path: '/',
            secure: $request->secure(),
            httpOnly: true,
            sameSite: 'lax',
        );
    }
}
