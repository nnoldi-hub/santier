<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AffiliatePartner;
use App\Models\PilotInvite;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use App\Support\AnalyticsTracker;
use App\Support\PricingPlan;
use App\Support\TenantContext;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

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
            'phone' => 'required|string|max:30',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $trialDays = (int) ($platformSettings['trial_days'] ?? 14);
        $tenantName = trim((string) $request->name) . ' Company';

        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => $this->generateTenantSlug($tenantName),
            'billing_plan' => 'pro',
            'billing_trial_ends_at' => now()->addDays($trialDays),
            'status' => 'active',
            'module_flags' => [],
            'affiliate_partner_id' => $this->resolveAffiliatePartnerId($request),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'current_tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'billing_plan' => 'pro',
            'billing_trial_ends_at' => now()->addDays($trialDays),
        ]);

        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $tenantAdminRole = Role::query()->whereNull('tenant_id')->where('guard_name', 'web')->firstWhere('name', 'tenant_admin');
        if ($tenantAdminRole) {
            $user->assignRole($tenantAdminRole);
        }

        $this->trackPilotInvite($tenant, $user);

        event(new Registered($user));

        Auth::login($user);

        AnalyticsTracker::track($request, 'register_completed', [
            'billing_plan' => PricingPlan::current($user),
        ], oncePerUser: true);

        return redirect(route('onboarding.show', absolute: false));
    }

    private function resolveAffiliatePartnerId(Request $request): ?int
    {
        $code = trim((string) $request->session()->get('affiliate_code', ''));
        if ($code === '') {
            return null;
        }

        return AffiliatePartner::query()
            ->where('code', $code)
            ->where('active', true)
            ->value('id');
    }

    /**
     * Links this registration into the sales pipeline: if the prospect
     * already has a PilotInvite (e.g. from the public demo-request form),
     * mark it converted; otherwise create one so staff see every new
     * trial, not just the ones who went through a lead form first.
     */
    private function trackPilotInvite(Tenant $tenant, User $user): void
    {
        $invite = PilotInvite::query()
            ->whereRaw('lower(contact_email) = ?', [strtolower($user->email)])
            ->latest('id')
            ->first();

        $attributes = [
            'company_name' => $tenant->name,
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'contact_phone' => $user->phone,
            'status' => 'trial_started',
            'commercial_stage' => 'trial',
            'converted_tenant_id' => $tenant->id,
            'last_contacted_at' => now(),
        ];

        if ($invite) {
            $invite->update($attributes);

            return;
        }

        PilotInvite::create([
            'tenant_id' => TenantContext::id(),
            'owner_id' => null,
            'invited_at' => now(),
            ...$attributes,
        ]);
    }

    private function generateTenantSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'firma';
        $slug = $baseSlug;
        $counter = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
