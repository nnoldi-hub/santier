<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDailyBriefingSetting;
use App\Models\TenantUser;
use App\Support\DailyBriefingBuilder;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        ]);
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
