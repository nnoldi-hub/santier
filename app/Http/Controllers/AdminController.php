<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PilotInvite;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    private const PAID_PLANS = ['starter', 'pro', 'enterprise'];

    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);

        $defaults = config('platform.defaults', []);

        return Inertia::render('Admin/Index', [
            'plans' => config('pricing.plans', []),
            'settings' => AppSetting::allWithDefaults($defaults),
            'users' => User::query()
                ->orderByDesc('created_at')
                ->get([
                    'id',
                    'name',
                    'email',
                    'billing_plan',
                    'billing_trial_ends_at',
                    'onboarding_completed_at',
                    'created_at',
                ]),
            'metrics' => [
                'users_total' => User::query()->count(),
                'users_paid' => User::query()->whereIn('billing_plan', ['starter', 'pro', 'enterprise'])->count(),
                'users_on_trial' => User::query()->whereNotNull('billing_trial_ends_at')->whereDate('billing_trial_ends_at', '>=', now()->toDateString())->count(),
                'monthly_mrr_estimate' => $this->estimateMonthlyRevenue(),
                'admin_count' => User::query()
                    ->where('is_superadmin', true)
                    ->orWhereIn('email', config('platform.admin_emails', []))
                    ->count(),
            ],
        ]);
    }

    public function tenantsIndex(Request $request): Response
    {
        $this->ensureAdmin($request);

        $filters = [
            'search' => trim((string) $request->string('search')->toString()),
            'plan' => trim((string) $request->string('plan')->toString()),
            'status' => trim((string) $request->string('status')->toString()),
        ];

        $today = Carbon::today();
        $plans = config('pricing.plans', []);

        $tenantQuery = Tenant::query()
            ->withCount([
                'memberships as total_memberships_count' => fn ($query) => $query,
                'memberships as active_memberships_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->withMax('users as latest_trial_ends_at', 'billing_trial_ends_at')
            ->orderByDesc('created_at');

        if ($filters['search'] !== '') {
            $tenantQuery->where(function ($query) use ($filters): void {
                $query
                    ->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('slug', 'like', "%{$filters['search']}%");
            });
        }

        if ($filters['plan'] !== '' && array_key_exists($filters['plan'], $plans)) {
            $tenantQuery->where('billing_plan', $filters['plan']);
        }

        if ($filters['status'] !== '' && in_array($filters['status'], ['active', 'suspended'], true)) {
            $tenantQuery->where('status', $filters['status']);
        }

        $tenants = $tenantQuery
            ->paginate(12)
            ->through(function (Tenant $tenant) use ($plans, $today): array {
                $trialEndsAt = $tenant->latest_trial_ends_at ? Carbon::parse((string) $tenant->latest_trial_ends_at) : null;
                $commercialStatus = $this->resolveTenantCommercialStatus($tenant, $trialEndsAt, $today);

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'billing_plan' => $tenant->billing_plan,
                    'billing_plan_label' => $plans[$tenant->billing_plan]['label'] ?? $tenant->billing_plan,
                    'status' => $tenant->status,
                    'commercial_status' => $commercialStatus,
                    'trial_ends_at' => optional($trialEndsAt)->toDateString(),
                    'active_memberships_count' => (int) ($tenant->active_memberships_count ?? 0),
                    'total_memberships_count' => (int) ($tenant->total_memberships_count ?? 0),
                    'estimated_mrr' => (int) ($plans[$tenant->billing_plan]['price'] ?? 0),
                    'created_at' => optional($tenant->created_at)->toDateString(),
                ];
            })
            ->withQueryString();

        $metrics = [
            'tenants_total' => Tenant::query()->count(),
            'tenants_paid' => Tenant::query()->whereIn('billing_plan', self::PAID_PLANS)->where('status', 'active')->count(),
            'tenants_trial' => Tenant::query()
                ->where('status', 'active')
                ->whereNotIn('billing_plan', self::PAID_PLANS)
                ->whereHas('users', fn ($query) => $query->whereDate('billing_trial_ends_at', '>=', $today->toDateString()))
                ->count(),
            'monthly_mrr_estimate' => (int) Tenant::query()->get(['billing_plan'])->sum(fn (Tenant $tenant) => (int) ($plans[$tenant->billing_plan]['price'] ?? 0)),
        ];

        $pilotInvitesTotal = PilotInvite::query()->count();
        $pilotWon = PilotInvite::query()->where('status', 'closed_won')->count();

        $pipeline = [
            'pilot_invites_total' => $pilotInvitesTotal,
            'demo_scheduled' => PilotInvite::query()->where('status', 'demo_scheduled')->count(),
            'trial_started' => PilotInvite::query()->where('status', 'trial_started')->count(),
            'closed_won' => $pilotWon,
            'pilot_to_paid_rate' => $pilotInvitesTotal > 0 ? round(($pilotWon / $pilotInvitesTotal) * 100, 2) : 0,
        ];

        return Inertia::render('Admin/TenantsIndex', [
            'tenants' => $tenants,
            'metrics' => $metrics,
            'pipeline' => $pipeline,
            'filters' => $filters,
            'planOptions' => collect($plans)->map(fn (array $plan, string $key) => ['value' => $key, 'label' => $plan['label'] ?? $key])->values(),
            'statusOptions' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'suspended', 'label' => 'Suspendate'],
            ],
        ]);
    }

    public function commercialDashboard(Request $request): Response
    {
        $this->ensureAdmin($request);

        $plans = config('pricing.plans', []);
        $today = Carbon::today();
        $currentMrr = (int) Tenant::query()->get(['billing_plan'])->sum(fn (Tenant $tenant) => (int) ($plans[$tenant->billing_plan]['price'] ?? 0));

        $pilotInvites = PilotInvite::query()->latest('id')->get();
        $statusCounts = [
            'invited' => $pilotInvites->where('status', 'invited')->count(),
            'contacted' => $pilotInvites->where('status', 'contacted')->count(),
            'demo_scheduled' => $pilotInvites->where('status', 'demo_scheduled')->count(),
            'trial_started' => $pilotInvites->where('status', 'trial_started')->count(),
            'closed_won' => $pilotInvites->where('status', 'closed_won')->count(),
            'closed_lost' => $pilotInvites->where('status', 'closed_lost')->count(),
        ];

        $qualifiedInvites = $pilotInvites->map(function (PilotInvite $invite) use ($plans): array {
            $qualification = $this->extractSalesQualification($invite->notes);
            $recommendedPlan = $this->resolveRecommendedPlanForInvite($qualification['estimated_users'], $qualification['customization_scope_label']);

            return [
                'status' => $invite->status,
                'estimated_users' => $qualification['estimated_users'],
                'customization_scope_label' => $qualification['customization_scope_label'],
                'recommended_plan' => $recommendedPlan,
                'recommended_mrr' => (int) ($plans[$recommendedPlan]['price'] ?? 0),
            ];
        });

        $weightedPipelineMrr = [
            'contacted' => (int) round($qualifiedInvites->where('status', 'contacted')->sum('recommended_mrr') * 0.18),
            'demo_scheduled' => (int) round($qualifiedInvites->where('status', 'demo_scheduled')->sum('recommended_mrr') * 0.35),
            'trial_started' => (int) round($qualifiedInvites->where('status', 'trial_started')->sum('recommended_mrr') * 0.65),
        ];

        $forecast = [
            'current_mrr' => $currentMrr,
            'forecast_30_days' => $currentMrr + (int) round($weightedPipelineMrr['contacted'] * 0.35) + (int) round($weightedPipelineMrr['demo_scheduled'] * 0.6) + (int) round($weightedPipelineMrr['trial_started'] * 0.85),
            'forecast_60_days' => $currentMrr + (int) round($weightedPipelineMrr['contacted'] * 0.7) + (int) round($weightedPipelineMrr['demo_scheduled'] * 0.9) + $weightedPipelineMrr['trial_started'],
            'forecast_90_days' => $currentMrr + $weightedPipelineMrr['contacted'] + $weightedPipelineMrr['demo_scheduled'] + $weightedPipelineMrr['trial_started'],
        ];

        $pilotInvitesTotal = max($pilotInvites->count(), 1);
        $trialStarted = $statusCounts['trial_started'] + $statusCounts['closed_won'];

        $conversion = [
            'pilot_to_demo' => round((($statusCounts['demo_scheduled'] + $statusCounts['trial_started'] + $statusCounts['closed_won']) / $pilotInvitesTotal) * 100, 2),
            'pilot_to_trial' => round(($trialStarted / $pilotInvitesTotal) * 100, 2),
            'pilot_to_paid' => round(($statusCounts['closed_won'] / $pilotInvitesTotal) * 100, 2),
        ];

        $tenantStats = [
            'tenants_total' => Tenant::query()->count(),
            'tenants_paid' => Tenant::query()->whereIn('billing_plan', self::PAID_PLANS)->where('status', 'active')->count(),
            'tenants_trial' => Tenant::query()
                ->where('status', 'active')
                ->whereNotIn('billing_plan', self::PAID_PLANS)
                ->whereHas('users', fn ($query) => $query->whereDate('billing_trial_ends_at', '>=', $today->toDateString()))
                ->count(),
            'tenants_at_risk' => Tenant::query()
                ->where('status', 'active')
                ->whereHas('users', fn ($query) => $query->whereBetween('billing_trial_ends_at', [$today->copy()->startOfDay(), $today->copy()->addDays(7)->endOfDay()]))
                ->count(),
        ];

        $recentCommercialSignals = $pilotInvites
            ->take(8)
            ->map(function (PilotInvite $invite) {
                $qualification = $this->extractSalesQualification($invite->notes);

                return [
                    'id' => $invite->id,
                    'company_name' => $invite->company_name,
                    'status' => $invite->status,
                    'estimated_users' => $qualification['estimated_users'],
                    'customization_scope_label' => $qualification['customization_scope_label'],
                    'created_at' => optional($invite->created_at)->toDateTimeString(),
                ];
            })
            ->values();

        return Inertia::render('Admin/CommercialDashboard', [
            'kpis' => [
                'current_mrr' => $forecast['current_mrr'],
                'tenants_paid' => $tenantStats['tenants_paid'],
                'tenants_trial' => $tenantStats['tenants_trial'],
                'tenants_at_risk' => $tenantStats['tenants_at_risk'],
            ],
            'funnel' => $statusCounts,
            'conversion' => $conversion,
            'forecast' => $forecast,
            'pipelineValue' => $weightedPipelineMrr,
            'tenantStats' => $tenantStats,
            'recentCommercialSignals' => $recentCommercialSignals,
        ]);
    }

    public function updateSubscription(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'billing_plan' => ['required', 'in:free,starter,pro,enterprise'],
            'billing_trial_ends_at' => ['nullable', 'date'],
            'onboarding_completed' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'billing_plan' => $validated['billing_plan'],
            'billing_trial_ends_at' => $validated['billing_trial_ends_at'] ?? null,
            'onboarding_completed_at' => !empty($validated['onboarding_completed']) ? ($user->onboarding_completed_at ?? now()) : null,
        ]);

        return back()->with('success', 'Abonamentul contului a fost actualizat.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'company_name' => ['required', 'string', 'max:120'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'support_email' => ['required', 'email', 'max:255'],
            'sales_email' => ['required', 'email', 'max:255'],
            'landing_video_url' => [
                'nullable',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->isAllowedLandingVideoUrl($value)) {
                        $fail('Linkul video trebuie sa fie YouTube sau URL direct catre fisier video (.mp4, .webm, .ogg).');
                    }
                },
            ],
            'landing_video_file' => ['nullable', 'file', 'mimes:mp4,webm,ogg,mov', 'max:102400'],
            'social_facebook_url' => ['nullable', 'url', 'max:500'],
            'social_instagram_url' => ['nullable', 'url', 'max:500'],
            'social_linkedin_url' => ['nullable', 'url', 'max:500'],
            'social_tiktok_url' => ['nullable', 'url', 'max:500'],
            'social_youtube_url' => ['nullable', 'url', 'max:500'],
            'document_logo_url' => ['nullable', 'url', 'max:500'],
            'document_logo_file' => ['nullable', 'image', 'max:2048'],
            'document_brand_color' => ['nullable', 'string', 'max:32'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:90'],
            'public_signup_enabled' => ['nullable', 'boolean'],
            'demo_mode_enabled' => ['nullable', 'boolean'],
        ]);

        $documentLogoUrl = $validated['document_logo_url'] ?? '';
        $landingVideoUrl = trim((string) ($validated['landing_video_url'] ?? ''));

        if ($request->hasFile('document_logo_file')) {
            $path = $request->file('document_logo_file')->store('branding', 'public');
            $documentLogoUrl = Storage::url($path);
        }

        if ($request->hasFile('landing_video_file')) {
            $path = $request->file('landing_video_file')->store('landing-videos', 'public');
            $landingVideoUrl = Storage::url($path);
        }

        AppSetting::setValues([
            'app_name' => $validated['app_name'],
            'company_name' => $validated['company_name'],
            'company_phone' => $validated['company_phone'] ?? '',
            'company_address' => $validated['company_address'] ?? '',
            'support_email' => $validated['support_email'],
            'sales_email' => $validated['sales_email'],
            'landing_video_url' => $landingVideoUrl,
            'social_facebook_url' => $validated['social_facebook_url'] ?? '',
            'social_instagram_url' => $validated['social_instagram_url'] ?? '',
            'social_linkedin_url' => $validated['social_linkedin_url'] ?? '',
            'social_tiktok_url' => $validated['social_tiktok_url'] ?? '',
            'social_youtube_url' => $validated['social_youtube_url'] ?? '',
            'document_logo_url' => $documentLogoUrl,
            'document_brand_color' => $validated['document_brand_color'] ?? '#f97316',
            'trial_days' => $validated['trial_days'],
            'public_signup_enabled' => (bool) ($validated['public_signup_enabled'] ?? false),
            'demo_mode_enabled' => (bool) ($validated['demo_mode_enabled'] ?? false),
        ]);

        return back()->with('success', 'Setarile globale au fost salvate.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($this->isAdmin($request->user()), 403);
    }

    private function isAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_superadmin ?? false)) {
            return true;
        }

        return in_array(strtolower($user->email), array_map('strtolower', config('platform.admin_emails', [])), true);
    }

    private function estimateMonthlyRevenue(): int
    {
        $plans = config('pricing.plans', []);

        return (int) User::query()->get(['billing_plan'])->sum(function (User $user) use ($plans) {
            return (int) ($plans[$user->billing_plan]['price'] ?? 0);
        });
    }

    private function isAllowedLandingVideoUrl(mixed $value): bool
    {
        $urlValue = trim((string) ($value ?? ''));

        if ($urlValue === '') {
            return true;
        }

        if (str_starts_with($urlValue, '/')) {
            return $this->isDirectVideoPath($urlValue);
        }

        $parts = parse_url($urlValue);

        if (! is_array($parts) || empty($parts['host'])) {
            return false;
        }

        $host = strtolower((string) $parts['host']);
        $allowedHosts = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com'];

        if (in_array($host, $allowedHosts, true)) {
            return true;
        }

        $path = strtolower((string) ($parts['path'] ?? ''));

        return $this->isDirectVideoPath($path);
    }

    private function isDirectVideoPath(string $path): bool
    {
        return (bool) preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $path);
    }

    private function extractSalesQualification(?string $notes): array
    {
        $content = (string) $notes;

        preg_match('/Utilizatori estimati:\s*(\d+)/i', $content, $usersMatch);
        preg_match('/Personalizare dorita:\s*(.+)/i', $content, $scopeMatch);

        return [
            'estimated_users' => isset($usersMatch[1]) ? (int) $usersMatch[1] : null,
            'customization_scope_label' => isset($scopeMatch[1]) ? trim($scopeMatch[1]) : null,
        ];
    }

    private function resolveRecommendedPlanForInvite(?int $estimatedUsers, ?string $customizationScopeLabel): string
    {
        $scope = strtolower((string) $customizationScopeLabel);
        $users = (int) ($estimatedUsers ?? 0);

        if (str_contains($scope, 'enterprise') || str_contains($scope, 'white-label') || str_contains($scope, 'domeniu')) {
            return 'enterprise';
        }

        if ($users >= 10 || str_contains($scope, 'template') || str_contains($scope, 'flux')) {
            return 'pro';
        }

        return 'starter';
    }

    private function resolveTenantCommercialStatus(Tenant $tenant, ?Carbon $trialEndsAt, Carbon $today): string
    {
        if ($tenant->status === 'suspended') {
            return 'Suspendata';
        }

        if (in_array($tenant->billing_plan, self::PAID_PLANS, true)) {
            return 'Platitoare';
        }

        if ($trialEndsAt && $trialEndsAt->greaterThanOrEqualTo($today)) {
            return 'Trial activ';
        }

        return 'Free';
    }
}