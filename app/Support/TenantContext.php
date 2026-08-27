<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TenantContext
{
    public static function user(): ?User
    {
        $authUser = Auth::user();

        return $authUser instanceof User ? $authUser : null;
    }

    public static function id(?User $user = null): int
    {
        $resolvedUser = $user ?? self::user();

        if ($resolvedUser) {
            $current = (int) ($resolvedUser->current_tenant_id ?? 0);
            if ($current > 0) {
                return $current;
            }

            $tenant = (int) ($resolvedUser->tenant_id ?? 0);
            if ($tenant > 0) {
                return $tenant;
            }
        }

        return (int) config('platform.defaults.default_tenant_id', 1);
    }

    public static function isSuperadmin(?User $user = null): bool
    {
        $resolvedUser = $user ?? self::user();

        return (bool) ($resolvedUser?->is_superadmin ?? false);
    }

    public static function isPlatformAdmin(?User $user = null): bool
    {
        $resolvedUser = $user ?? self::user();

        if (! $resolvedUser) {
            return false;
        }

        if (strtolower((string) $resolvedUser->email) === strtolower((string) config('demo.email', 'demo@modulia.ro'))) {
            return false;
        }

        return self::isSuperadmin($resolvedUser)
            || in_array(strtolower($resolvedUser->email), array_map('strtolower', config('platform.admin_emails', [])), true);
    }
}
