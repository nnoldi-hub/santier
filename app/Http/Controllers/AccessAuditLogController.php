<?php

namespace App\Http\Controllers;

use App\Models\AccessAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccessAuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $request->user();
        $this->authorizeAuditRead($actor);

        $data = $this->validatedFilters($request);

        $isSuperadmin = TenantContext::isSuperadmin($actor);
        $tenantId = TenantContext::id($actor);
        $scopedQuery = $this->baseScopedQuery($actor, $data);
        $query = $this->applyFilters((clone $scopedQuery)->with('actor:id,name,email')->orderByDesc('id'), $data);

        $logs = $query
            ->paginate(30)
            ->withQueryString()
            ->through(function (AccessAuditLog $log): array {
                return [
                    'id' => $log->id,
                    'tenant_id' => $log->tenant_id,
                    'action' => $log->action,
                    'resource_type' => $log->resource_type,
                    'resource_id' => $log->resource_id,
                    'metadata' => $log->metadata ?? [],
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => optional($log->created_at)->toDateTimeString(),
                    'actor' => [
                        'id' => $log->actor?->id,
                        'name' => $log->actor?->name,
                        'email' => $log->actor?->email,
                    ],
                ];
            });

        $actionOptions = (clone $scopedQuery)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values();

        $resourceTypeOptions = (clone $scopedQuery)
            ->whereNotNull('resource_type')
            ->where('resource_type', '!=', '')
            ->select('resource_type')
            ->distinct()
            ->orderBy('resource_type')
            ->pluck('resource_type')
            ->values();

        return Inertia::render('Account/Audit', [
            'logs' => $logs,
            'filters' => [
                'action' => $data['action'] ?? '',
                'actor' => $data['actor'] ?? '',
                'resource_type' => $data['resource_type'] ?? '',
                'tenant_id' => $isSuperadmin ? ($data['tenant_id'] ?? '') : $tenantId,
                'from' => $data['from'] ?? '',
                'to' => $data['to'] ?? '',
            ],
            'actionOptions' => $actionOptions,
            'resourceTypeOptions' => $resourceTypeOptions,
            'tenantOptions' => $isSuperadmin
                ? Tenant::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'isSuperadmin' => $isSuperadmin,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $actor = $request->user();
        $this->authorizeAuditRead($actor);

        $data = $this->validatedFilters($request);

        $rows = $this->applyFilters(
            $this->baseScopedQuery($actor, $data)->with('actor:id,name,email')->orderByDesc('id'),
            $data
        )
            ->limit(5000)
            ->get()
            ->map(function (AccessAuditLog $log): array {
                return [
                    optional($log->created_at)->toDateTimeString(),
                    (string) ($log->action ?? ''),
                    (string) ($log->resource_type ?? ''),
                    (string) ($log->resource_id ?? ''),
                    (string) ($log->tenant_id ?? ''),
                    (string) ($log->actor?->name ?? ''),
                    (string) ($log->actor?->email ?? ''),
                    (string) ($log->ip_address ?? ''),
                    json_encode($log->metadata ?? [], JSON_UNESCAPED_UNICODE),
                ];
            })
            ->all();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['created_at', 'action', 'resource_type', 'resource_id', 'tenant_id', 'actor_name', 'actor_email', 'ip_address', 'metadata']);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'audit-iam.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'action' => ['nullable', 'string', 'max:120'],
            'actor' => ['nullable', 'string', 'max:255'],
            'resource_type' => ['nullable', 'string', 'max:120'],
            'tenant_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
    }

    private function baseScopedQuery(User $actor, array $filters): Builder
    {
        $query = AccessAuditLog::query();

        if (TenantContext::isSuperadmin($actor)) {
            if (!empty($filters['tenant_id'])) {
                $query->where('tenant_id', (int) $filters['tenant_id']);
            }

            return $query;
        }

        return $query->where('tenant_id', TenantContext::id($actor));
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['action'])) {
            $query->where('action', 'like', '%' . trim((string) $filters['action']) . '%');
        }

        if (!empty($filters['resource_type'])) {
            $query->where('resource_type', trim((string) $filters['resource_type']));
        }

        if (!empty($filters['actor'])) {
            $actorSearch = trim((string) $filters['actor']);
            $query->whereHas('actor', function ($subQuery) use ($actorSearch): void {
                $subQuery
                    ->where('name', 'like', '%' . $actorSearch . '%')
                    ->orWhere('email', 'like', '%' . $actorSearch . '%');
            });
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', (string) $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', (string) $filters['to']);
        }

        return $query;
    }

    private function authorizeAuditRead(User $user): void
    {
        if (TenantContext::isSuperadmin($user) || $user->hasRole('tenant_admin')) {
            return;
        }

        if ($this->legacyAllow($user)) {
            return;
        }

        abort(403);
    }

    private function legacyAllow(User $user): bool
    {
        return $user->roles()->count() === 0 && $user->permissions()->count() === 0;
    }
}
