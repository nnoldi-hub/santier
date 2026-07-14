<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Carbon;

class PricingPlan
{
    public static function current(User $user): string
    {
        $tenant = self::tenant($user);
        $plan = $tenant?->billing_plan ?: ($user->billing_plan ?? 'free');

        return array_key_exists($plan, config('pricing.plans', [])) ? $plan : 'free';
    }

    public static function trialEndsAt(User $user): ?Carbon
    {
        $tenant = self::tenant($user);
        $value = $tenant?->billing_trial_ends_at ?: $user->billing_trial_ends_at;

        if ($value instanceof Carbon) {
            return $value;
        }

        return $value ? Carbon::parse((string) $value) : null;
    }

    public static function label(User $user): string
    {
        $plan = self::current($user);

        return config("pricing.plans.{$plan}.label", 'Free');
    }

    public static function projectLimit(User $user): ?int
    {
        $plan = self::current($user);

        return config("pricing.plans.{$plan}.project_limit");
    }

    public static function hasFeature(User $user, string $feature): bool
    {
        $plan = self::current($user);

        return (bool) config("pricing.plans.{$plan}.features.{$feature}", false);
    }

    public static function canCreateProject(User $user): bool
    {
        $limit = self::projectLimit($user);

        if ($limit === null) {
            return true;
        }

        $currentCount = Project::query()
            ->where('tenant_id', TenantContext::id($user))
            ->where('created_by', $user->id)
            ->count();

        return $currentCount < $limit;
    }

    public static function projectLimitMessage(User $user): string
    {
        $limit = self::projectLimit($user);

        if ($limit === null) {
            return 'Planul curent nu are limita la numarul de proiecte.';
        }

        return "Ai atins limita de {$limit} proiect(e) pentru planul " . self::label($user) . '. Upgrade necesar pentru proiecte suplimentare.';
    }

    public static function usersLimit(User $user): ?int
    {
        $plan = self::current($user);

        return config("pricing.plans.{$plan}.users_limit");
    }

    public static function canInviteUser(User $actor): bool
    {
        $limit = self::usersLimit($actor);

        if ($limit === null) {
            return true;
        }

        $currentCount = TenantUser::query()
            ->where('tenant_id', TenantContext::id($actor))
            ->where('status', 'active')
            ->count();

        return $currentCount < $limit;
    }

    public static function usersLimitMessage(User $user): string
    {
        $limit = self::usersLimit($user);

        if ($limit === null) {
            return 'Planul curent nu are limita la numarul de utilizatori.';
        }

        return "Ai atins limita de {$limit} utilizator(i) activi pentru planul " . self::label($user) . '. Upgrade necesar pentru a invita utilizatori suplimentari.';
    }

    public static function featureMessage(string $feature): string
    {
        return match ($feature) {
            'gantt' => 'Gantt este disponibil incepand cu planul Starter.',
            'exports_csv' => 'Exporturile sunt disponibile incepand cu planul Starter.',
            'exports_enterprise' => 'Exporturile enterprise (XLSX/PDF/subscriptions) sunt disponibile incepand cu planul Pro.',
            'document_branding' => 'Configurarea documentelor este disponibila pentru conturile cu abonament platit.',
            default => 'Functionalitatea nu este disponibila pe planul curent.',
        };
    }

    public static function tenant(User $user): ?Tenant
    {
        return $user->currentTenant ?: $user->tenant;
    }
}
