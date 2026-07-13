<?php

namespace App\Http\Controllers;

use App\Exports\CommercialDashboardWorkbookExport;
use App\Models\AppSetting;
use App\Models\AccessAuditLog;
use App\Models\CommercialTask;
use App\Models\PilotInvite;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    private const PAID_PLANS = ['starter', 'pro', 'enterprise'];

    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);

        $defaults = config('platform.defaults', []);
        $users = User::query()
            ->with([
                'currentTenant:id,name,slug,billing_plan,billing_trial_ends_at',
                'tenant:id,name,slug,billing_plan,billing_trial_ends_at',
            ])
            ->orderByDesc('created_at')
            ->get([
                'id',
                'name',
                'email',
                'tenant_id',
                'current_tenant_id',
                'billing_plan',
                'billing_trial_ends_at',
                'onboarding_completed_at',
                'created_at',
            ])
            ->map(function (User $user): array {
                $tenant = $user->currentTenant ?: $user->tenant;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tenant_id' => $tenant?->id,
                    'tenant_name' => $tenant?->name,
                    'tenant_slug' => $tenant?->slug,
                    'billing_plan' => $tenant?->billing_plan ?: $user->billing_plan,
                    'billing_trial_ends_at' => optional($tenant?->billing_trial_ends_at ?: $user->billing_trial_ends_at)?->toDateString(),
                    'onboarding_completed_at' => optional($user->onboarding_completed_at)?->toDateString(),
                    'created_at' => optional($user->created_at)?->toDateString(),
                ];
            })
            ->values();

        return Inertia::render('Admin/Index', [
            'plans' => config('pricing.plans', []),
            'settings' => AppSetting::allWithDefaults($defaults),
            'users' => $users,
            'metrics' => [
                'users_total' => $users->count(),
                'users_paid' => $users->whereIn('billing_plan', self::PAID_PLANS)->count(),
                'users_on_trial' => $users->filter(fn (array $user) => ! empty($user['billing_trial_ends_at']) && $user['billing_trial_ends_at'] >= now()->toDateString())->count(),
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
                $trialEndsAt = $tenant->billing_trial_ends_at ? Carbon::parse((string) $tenant->billing_trial_ends_at) : null;
                $commercialStatus = $this->resolveTenantCommercialStatus($tenant, $trialEndsAt, $today);
                $daysToTrialEnd = $trialEndsAt ? $today->diffInDays($trialEndsAt, false) : null;
                $riskLevel = 'low';

                if ($tenant->status === 'suspended') {
                    $riskLevel = 'high';
                } elseif (! in_array($tenant->billing_plan, self::PAID_PLANS, true) && $daysToTrialEnd !== null && $daysToTrialEnd <= 7) {
                    $riskLevel = 'high';
                } elseif (! in_array($tenant->billing_plan, self::PAID_PLANS, true) && $daysToTrialEnd !== null && $daysToTrialEnd <= 14) {
                    $riskLevel = 'medium';
                }

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'billing_plan' => $tenant->billing_plan,
                    'billing_plan_label' => $plans[$tenant->billing_plan]['label'] ?? $tenant->billing_plan,
                    'status' => $tenant->status,
                    'commercial_status' => $commercialStatus,
                    'trial_ends_at' => optional($trialEndsAt)->toDateString(),
                    'days_to_trial_end' => $daysToTrialEnd,
                    'risk_level' => $riskLevel,
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
                ->whereDate('billing_trial_ends_at', '>=', $today->toDateString())
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

        $attention = [
            'trial_expiring_soon' => Tenant::query()
                ->where('status', 'active')
                ->whereNotIn('billing_plan', self::PAID_PLANS)
                ->whereBetween('billing_trial_ends_at', [$today->copy()->startOfDay(), $today->copy()->addDays(7)->endOfDay()])
                ->count(),
            'suspended_tenants' => Tenant::query()->where('status', 'suspended')->count(),
            'free_without_active_trial' => Tenant::query()
                ->where('status', 'active')
                ->whereNotIn('billing_plan', self::PAID_PLANS)
                ->where(function ($query) use ($today): void {
                    $query
                        ->whereNull('billing_trial_ends_at')
                        ->orWhereDate('billing_trial_ends_at', '<', $today->toDateString());
                })
                ->count(),
        ];

        $recentCommercialActions = AccessAuditLog::query()
            ->with('actor:id,name,email')
            ->where('action', 'admin.tenant.commercial.updated')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function (AccessAuditLog $log): array {
                return [
                    'id' => $log->id,
                    'tenant_id' => $log->tenant_id,
                    'tenant_name' => (string) (($log->metadata['tenant_name'] ?? '') ?: ('Tenant #' . (string) $log->tenant_id)),
                    'changes' => [
                        'billing_plan' => [
                            'from' => $log->metadata['old']['billing_plan'] ?? null,
                            'to' => $log->metadata['new']['billing_plan'] ?? null,
                        ],
                        'status' => [
                            'from' => $log->metadata['old']['status'] ?? null,
                            'to' => $log->metadata['new']['status'] ?? null,
                        ],
                        'billing_trial_ends_at' => [
                            'from' => $log->metadata['old']['billing_trial_ends_at'] ?? null,
                            'to' => $log->metadata['new']['billing_trial_ends_at'] ?? null,
                        ],
                    ],
                    'actor_name' => $log->actor?->name,
                    'actor_email' => $log->actor?->email,
                    'created_at' => optional($log->created_at)->toDateTimeString(),
                ];
            })
            ->values();

        return Inertia::render('Admin/TenantsIndex', [
            'tenants' => $tenants,
            'metrics' => $metrics,
            'pipeline' => $pipeline,
            'attention' => $attention,
            'recentCommercialActions' => $recentCommercialActions,
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

        return Inertia::render('Admin/CommercialDashboard', $this->buildCommercialDashboardPayload());
    }

    public function exportCommercialXlsx(Request $request)
    {
        $this->ensureAdmin($request);

        return Excel::download(
            new CommercialDashboardWorkbookExport($this->buildCommercialDashboardPayload()),
            'dashboard-comercial-board.xlsx'
        );
    }

    private function buildCommercialDashboardPayload(): array
    {

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

        $stageCounts = [
            'prospecting' => $pilotInvites->where('commercial_stage', 'prospecting')->count(),
            'contacted' => $pilotInvites->where('commercial_stage', 'contacted')->count(),
            'follow_up' => $pilotInvites->where('commercial_stage', 'follow_up')->count(),
            'demo' => $pilotInvites->where('commercial_stage', 'demo')->count(),
            'trial' => $pilotInvites->where('commercial_stage', 'trial')->count(),
            'negotiation' => $pilotInvites->where('commercial_stage', 'negotiation')->count(),
            'won' => $pilotInvites->where('commercial_stage', 'won')->count(),
            'lost' => $pilotInvites->where('commercial_stage', 'lost')->count(),
        ];

        $qualifiedInvites = $pilotInvites->map(function (PilotInvite $invite) use ($plans): array {
            $qualification = $this->extractSalesQualification($invite->notes);
            $recommendedPlan = $this->resolveRecommendedPlanForInvite($qualification['estimated_users'], $qualification['customization_scope_label']);

            return [
                'status' => $invite->status,
                'commercial_stage' => $invite->commercial_stage,
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
                ->whereDate('billing_trial_ends_at', '>=', $today->toDateString())
                ->count(),
            'tenants_at_risk' => Tenant::query()
                ->where('status', 'active')
                ->whereBetween('billing_trial_ends_at', [$today->copy()->startOfDay(), $today->copy()->addDays(7)->endOfDay()])
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
                    'commercial_stage' => $invite->commercial_stage,
                    'estimated_users' => $qualification['estimated_users'],
                    'customization_scope_label' => $qualification['customization_scope_label'],
                    'created_at' => optional($invite->created_at)->toDateTimeString(),
                ];
            })
            ->values();

        $trialRiskTenants = Tenant::query()
            ->where('status', 'active')
            ->whereNotIn('billing_plan', self::PAID_PLANS)
            ->withCount([
                'memberships as active_memberships_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->get()
            ->map(function (Tenant $tenant) use ($today): ?array {
                $trialEndsAt = $tenant->billing_trial_ends_at ? Carbon::parse((string) $tenant->billing_trial_ends_at) : null;

                if (! $trialEndsAt || $trialEndsAt->lt($today) || $trialEndsAt->gt($today->copy()->addDays(7))) {
                    return null;
                }

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'billing_plan' => $tenant->billing_plan,
                    'trial_ends_at' => $trialEndsAt->toDateString(),
                    'days_left' => $today->diffInDays($trialEndsAt, false),
                    'active_memberships_count' => (int) ($tenant->active_memberships_count ?? 0),
                ];
            })
            ->filter()
            ->sortBy('days_left')
            ->values();

        $riskScoredTenants = Tenant::query()
            ->where('status', 'active')
            ->withCount([
                'memberships as active_memberships_count' => fn ($query) => $query->where('status', 'active'),
                'memberships as onboarding_incomplete_memberships_count' => fn ($query) => $query
                    ->where('status', 'active')
                    ->whereHas('user', fn ($userQuery) => $userQuery->whereNull('onboarding_completed_at')),
            ])
            ->get()
            ->map(function (Tenant $tenant) use ($today): array {
                $trialEndsAt = $tenant->billing_trial_ends_at ? Carbon::parse((string) $tenant->billing_trial_ends_at) : null;
                $trialExpiring = $trialEndsAt
                    && ! in_array($tenant->billing_plan, self::PAID_PLANS, true)
                    && $trialEndsAt->greaterThanOrEqualTo($today)
                    && $trialEndsAt->lessThanOrEqualTo($today->copy()->addDays(7));

                $onboardingIncompleteCount = (int) ($tenant->onboarding_incomplete_memberships_count ?? 0);
                $churnSignal = (int) ($tenant->active_memberships_count ?? 0) <= 1;

                $score = 0;
                if ($trialExpiring) {
                    $score += 45;
                }
                if ($onboardingIncompleteCount > 0) {
                    $score += min(35, 10 + ($onboardingIncompleteCount * 5));
                }
                if ($churnSignal) {
                    $score += 20;
                }

                $score = min(100, $score);
                $riskLevel = $score >= 70 ? 'high' : ($score >= 40 ? 'medium' : 'low');

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'billing_plan' => $tenant->billing_plan,
                    'active_memberships_count' => (int) ($tenant->active_memberships_count ?? 0),
                    'onboarding_incomplete_memberships_count' => $onboardingIncompleteCount,
                    'trial_ends_at' => optional($trialEndsAt)->toDateString(),
                    'trial_expiring_soon' => (bool) $trialExpiring,
                    'churn_signal' => (bool) $churnSignal,
                    'risk_score' => $score,
                    'risk_level' => $riskLevel,
                ];
            })
            ->filter(fn (array $tenant) => $tenant['risk_score'] > 0)
            ->sortByDesc('risk_score')
            ->values();

        $riskOverview = [
            'high_risk_count' => $riskScoredTenants->where('risk_level', 'high')->count(),
            'medium_risk_count' => $riskScoredTenants->where('risk_level', 'medium')->count(),
            'tenants_with_onboarding_gap' => $riskScoredTenants->where('onboarding_incomplete_memberships_count', '>', 0)->count(),
            'tenants_with_churn_signal' => $riskScoredTenants->where('churn_signal', true)->count(),
        ];

        $topPipelineOpportunities = $qualifiedInvites
            ->filter(fn (array $invite) => in_array($invite['status'], ['contacted', 'demo_scheduled', 'trial_started'], true))
            ->sortByDesc('recommended_mrr')
            ->take(8)
            ->values();

        $stagnantThreshold = $today->copy()->subDays((int) config('pilot_invites.stagnant_days', 14));

        $tasksTodayBase = CommercialTask::query()
            ->whereIn('status', ['todo', 'in_progress'])
            ->whereDate('due_at', $today->toDateString());
        $tasksToday = [
            'count' => (clone $tasksTodayBase)->count(),
            'items' => (clone $tasksTodayBase)
                ->with('pilotInvite:id,company_name')
                ->orderBy('due_at')
                ->take(5)
                ->get()
                ->map(fn (CommercialTask $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_at' => optional($task->due_at)->toDateTimeString(),
                    'priority' => $task->priority,
                    'pilot_invite_id' => $task->pilot_invite_id,
                    'company_name' => $task->pilotInvite?->company_name,
                ])
                ->values(),
        ];

        $followUpOverdueBase = PilotInvite::query()
            ->whereIn('status', PilotInvite::ACTIVE_STATUSES)
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<', now());
        $followUpOverdue = [
            'count' => (clone $followUpOverdueBase)->count(),
            'items' => (clone $followUpOverdueBase)
                ->orderBy('follow_up_at')
                ->take(5)
                ->get(['id', 'company_name', 'follow_up_at'])
                ->map(fn (PilotInvite $invite) => [
                    'id' => $invite->id,
                    'company_name' => $invite->company_name,
                    'follow_up_at' => optional($invite->follow_up_at)->toDateTimeString(),
                ])
                ->values(),
        ];

        $stagnantBase = PilotInvite::query()
            ->whereIn('status', PilotInvite::ACTIVE_STATUSES)
            ->where(fn ($query) => $query
                ->whereNull('last_contacted_at')
                ->orWhere('last_contacted_at', '<', $stagnantThreshold));
        $stagnantOpportunities = [
            'count' => (clone $stagnantBase)->count(),
            'items' => (clone $stagnantBase)
                ->orderBy('last_contacted_at')
                ->take(5)
                ->get(['id', 'company_name', 'last_contacted_at'])
                ->map(fn (PilotInvite $invite) => [
                    'id' => $invite->id,
                    'company_name' => $invite->company_name,
                    'last_contacted_at' => optional($invite->last_contacted_at)->toDateTimeString(),
                ])
                ->values(),
        ];

        $pendingHandoffsBase = PilotInvite::query()
            ->where('status', 'closed_won')
            ->whereNull('onboarding_handoff_at');
        $pendingHandoffs = [
            'count' => (clone $pendingHandoffsBase)->count(),
            'items' => (clone $pendingHandoffsBase)
                ->orderBy('updated_at')
                ->take(5)
                ->get(['id', 'company_name', 'updated_at'])
                ->map(fn (PilotInvite $invite) => [
                    'id' => $invite->id,
                    'company_name' => $invite->company_name,
                    'closed_at' => optional($invite->updated_at)->toDateTimeString(),
                ])
                ->values(),
        ];

        $inbox = [
            'tasks_today' => $tasksToday,
            'follow_up_overdue' => $followUpOverdue,
            'stagnant_opportunities' => $stagnantOpportunities,
            'pending_handoffs' => $pendingHandoffs,
        ];

        return [
            'kpis' => [
                'current_mrr' => $forecast['current_mrr'],
                'tenants_paid' => $tenantStats['tenants_paid'],
                'tenants_trial' => $tenantStats['tenants_trial'],
                'tenants_at_risk' => $tenantStats['tenants_at_risk'],
            ],
            'funnel' => $statusCounts,
            'stageFunnel' => $stageCounts,
            'conversion' => $conversion,
            'forecast' => $forecast,
            'pipelineValue' => $weightedPipelineMrr,
            'tenantStats' => $tenantStats,
            'recentCommercialSignals' => $recentCommercialSignals,
            'trialRiskTenants' => $trialRiskTenants,
            'riskOverview' => $riskOverview,
            'riskScoredTenants' => $riskScoredTenants->take(8)->values(),
            'topPipelineOpportunities' => $topPipelineOpportunities,
            'inbox' => $inbox,
        ];
    }

    public function exportCommercialCsv(Request $request): StreamedResponse
    {
        $this->ensureAdmin($request);

        $plans = config('pricing.plans', []);
        $rows = Tenant::query()
            ->withCount([
                'memberships as active_memberships_count' => fn ($query) => $query->where('status', 'active'),
                'memberships as total_memberships_count' => fn ($query) => $query,
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Tenant $tenant) use ($plans): array {
                $trialEndsAt = $tenant->billing_trial_ends_at ? Carbon::parse((string) $tenant->billing_trial_ends_at) : null;

                return [
                    $tenant->id,
                    $tenant->name,
                    $tenant->slug,
                    $tenant->billing_plan,
                    $plans[$tenant->billing_plan]['label'] ?? $tenant->billing_plan,
                    $tenant->status,
                    $this->resolveTenantCommercialStatus($tenant, $trialEndsAt, Carbon::today()),
                    (int) ($tenant->active_memberships_count ?? 0),
                    (int) ($tenant->total_memberships_count ?? 0),
                    optional($trialEndsAt)->toDateString(),
                    (int) ($plans[$tenant->billing_plan]['price'] ?? 0),
                    optional($tenant->created_at)->toDateString(),
                ];
            })
            ->all();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['tenant_id', 'name', 'slug', 'billing_plan', 'billing_plan_label', 'platform_status', 'commercial_status', 'active_memberships', 'total_memberships', 'trial_ends_at', 'estimated_mrr', 'created_at']);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'dashboard-comercial-firme.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
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

        $tenant = $user->currentTenant ?: $user->tenant;

        if ($tenant instanceof Tenant) {
            $tenant->update([
                'billing_plan' => $validated['billing_plan'],
                'billing_trial_ends_at' => $validated['billing_trial_ends_at'] ?? null,
            ]);
        }

        $user->update([
            'billing_plan' => $validated['billing_plan'],
            'billing_trial_ends_at' => $validated['billing_trial_ends_at'] ?? null,
            'onboarding_completed_at' => !empty($validated['onboarding_completed']) ? ($user->onboarding_completed_at ?? now()) : null,
        ]);

        return back()->with('success', 'Abonamentul contului a fost actualizat.');
    }

    public function updateTenantCommercial(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->ensureAdmin($request);

        $oldCommercialState = [
            'billing_plan' => $tenant->billing_plan,
            'status' => $tenant->status,
            'billing_trial_ends_at' => optional($tenant->billing_trial_ends_at)->toDateString(),
        ];

        $planKeys = array_keys(config('pricing.plans', []));

        $validated = $request->validate([
            'billing_plan' => ['required', 'string', Rule::in($planKeys)],
            'status' => ['required', 'string', Rule::in(['active', 'suspended'])],
            'billing_trial_ends_at' => ['nullable', 'date'],
        ]);

        $requiresTrialDate = $validated['status'] === 'active' && ! in_array($validated['billing_plan'], self::PAID_PLANS, true);
        $trialEndsAt = $validated['billing_trial_ends_at'] ?? null;

        if ($requiresTrialDate && empty($trialEndsAt)) {
            return back()->withErrors([
                'billing_trial_ends_at' => 'Pentru planuri neplatite active, data de final trial este obligatorie.',
            ]);
        }

        if (! empty($trialEndsAt) && Carbon::parse((string) $trialEndsAt)->lt(Carbon::today())) {
            return back()->withErrors([
                'billing_trial_ends_at' => 'Data de final trial nu poate fi in trecut.',
            ]);
        }

        if (! $requiresTrialDate) {
            $trialEndsAt = null;
        }

        $tenant->update([
            'billing_plan' => $validated['billing_plan'],
            'status' => $validated['status'],
            'billing_trial_ends_at' => $trialEndsAt,
        ]);

        // Compat mode: keep user-level billing fields aligned while legacy screens still read them.
        $tenantUserIds = $tenant->memberships()->pluck('user_id');

        if ($tenantUserIds->isNotEmpty()) {
            User::query()
                ->whereIn('id', $tenantUserIds)
                ->update([
                    'billing_plan' => $validated['billing_plan'],
                    'billing_trial_ends_at' => $trialEndsAt,
                ]);
        }

        AccessAuditLog::query()->create([
            'tenant_id' => $tenant->id,
            'actor_user_id' => $request->user()?->id,
            'action' => 'admin.tenant.commercial.updated',
            'resource_type' => 'tenant',
            'resource_id' => $tenant->id,
            'metadata' => [
                'tenant_name' => $tenant->name,
                'old' => $oldCommercialState,
                'new' => [
                    'billing_plan' => $validated['billing_plan'],
                    'status' => $validated['status'],
                    'billing_trial_ends_at' => $trialEndsAt,
                ],
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Firma a fost actualizata.');
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
            'document_logo_url' => [
                'nullable',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->isAllowedAssetUrl($value)) {
                        $fail('Logo-ul trebuie sa fie un URL valid sau o cale relativa care incepe cu /.');
                    }
                },
            ],
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

        return (int) Tenant::query()->get(['billing_plan'])->sum(function (Tenant $tenant) use ($plans) {
            return (int) ($plans[$tenant->billing_plan]['price'] ?? 0);
        });
    }

    private function isAllowedAssetUrl(mixed $value): bool
    {
        $urlValue = trim((string) ($value ?? ''));

        if ($urlValue === '') {
            return true;
        }

        if (str_starts_with($urlValue, '/')) {
            return true;
        }

        return (bool) filter_var($urlValue, FILTER_VALIDATE_URL);
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