<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDailyBriefingLog;
use App\Models\ProjectDailyBriefingSetting;
use App\Models\TenantUser;
use App\Support\DailyBriefingBuilder;
use App\Support\DailyBriefingPdfExporter;
use App\Support\DocumentBranding;
use App\Support\ExportAudit;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DailyBriefingController extends Controller
{
    public function show(Request $request, Project $project): Response
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $date = $request->date('date') ? Carbon::parse($request->date('date')) : null;
        $briefing = DailyBriefingBuilder::build($project, $date);

        $settings = ProjectDailyBriefingSetting::firstOrNew(['project_id' => $project->id]);
        $channels = array_merge(ProjectDailyBriefingSetting::$defaultChannels, $settings->channels ?? []);

        return Inertia::render('DailyBriefing/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'briefing' => $briefing,
            'settings' => [
                'enabled' => (bool) $settings->enabled,
                'send_time' => $settings->send_time?->format('H:i') ?? '07:30',
                'recipient_user_ids' => $settings->recipient_user_ids ?? [],
                'detail_level' => $settings->detail_level ?? 'complet',
                'channels' => $channels,
            ],
            'detailLevels' => ProjectDailyBriefingSetting::$detailLevelLabels,
            'tenantUsers' => TenantUser::where('tenant_id', $tenantId)
                ->with('user:id,name,email')
                ->get()
                ->map(fn (TenantUser $membership) => [
                    'id' => $membership->user_id,
                    'name' => $membership->user?->name,
                    'email' => $membership->user?->email,
                ])
                ->filter(fn (array $row) => $row['name'] !== null)
                ->values(),
            'history' => ProjectDailyBriefingLog::where('project_id', $project->id)
                ->orderByDesc('briefing_date')
                ->limit(60)
                ->get(['id', 'briefing_date', 'sent_at', 'risk_level', 'blockers_count', 'recipients_count', 'channels', 'snapshot'])
                ->map(fn (ProjectDailyBriefingLog $log) => [
                    'id' => $log->id,
                    'briefing_date' => $log->briefing_date->toDateString(),
                    'sent_at' => $log->sent_at->toDateTimeString(),
                    'risk_level' => $log->risk_level,
                    'blockers_count' => $log->blockers_count,
                    'recipients_count' => $log->recipients_count,
                    'channels' => $log->channels,
                    'snapshot' => $log->snapshot,
                ]),
        ]);
    }

    public function pdf(Request $request, Project $project)
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $briefing = DailyBriefingBuilder::build($project);
        $sections = DailyBriefingPdfExporter::buildSections($briefing);
        $fileName = 'memento-zilnic-' . Str::slug($project->name) . '-' . now()->format('Ymd_His') . '.pdf';

        $branding = DocumentBranding::resolve($tenantId);

        ExportAudit::log('daily-briefing-pdf', 'pdf', ['project_id' => $project->id], [
            'file_name' => $fileName,
        ]);

        return Pdf::loadView('exports.managerial-pdf', [
            'title' => 'Memento zilnic - ' . $project->name,
            'branding' => [
                'company_name' => $branding['company_name'] ?? config('exports.company_name'),
                'company_email' => $branding['support_email'] ?? config('exports.company_email'),
                'company_phone' => $branding['company_phone'] ?? config('exports.company_phone'),
                'company_address' => $branding['company_address'] ?? '',
                'document_logo_url' => $branding['document_logo_url'] ?? '',
                'brand_color' => $branding['document_brand_color'] ?? config('exports.brand_color'),
                'white_label' => $branding['white_label'],
            ],
            'generatedAt' => now()->toDateTimeString(),
            'filters' => ['project' => $project->name, 'data' => $briefing['date']],
            'sections' => $sections,
        ])->setOptions([
            'isRemoteEnabled' => true,
        ])->setPaper('a4')->download($fileName);
    }

    public function updateSettings(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'send_time' => ['required', 'date_format:H:i'],
            'recipient_user_ids' => ['nullable', 'array'],
            'recipient_user_ids.*' => ['integer', Rule::exists('tenant_users', 'user_id')->where('tenant_id', $tenantId)],
            'detail_level' => ['required', Rule::in(array_keys(ProjectDailyBriefingSetting::$detailLevelLabels))],
            'channels' => ['nullable', 'array'],
            'channels.email' => ['boolean'],
            'channels.in_app' => ['boolean'],
        ]);

        ProjectDailyBriefingSetting::updateOrCreate(
            ['project_id' => $project->id],
            [
                'tenant_id' => $tenantId,
                'enabled' => $validated['enabled'],
                'send_time' => $validated['send_time'],
                'recipient_user_ids' => $validated['recipient_user_ids'] ?? [],
                'detail_level' => $validated['detail_level'],
                'channels' => array_merge(ProjectDailyBriefingSetting::$defaultChannels, $validated['channels'] ?? []),
            ]
        );

        return back()->with('success', 'Setarile mementoului zilnic au fost salvate.');
    }
}
