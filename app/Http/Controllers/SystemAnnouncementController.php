<?php

namespace App\Http\Controllers;

use App\Models\SystemAnnouncement;
use App\Support\AccessAudit;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SystemAnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(TenantContext::isPlatformAdmin($request->user()), 403);

        return Inertia::render('Admin/SystemAnnouncements', [
            'announcements' => SystemAnnouncement::query()->latest('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(TenantContext::isPlatformAdmin($request->user()), 403);

        $announcement = SystemAnnouncement::query()->create($this->validated($request));
        AccessAudit::log('platform.announcement.created', $request->user(), $request, 'system_announcement', $announcement->id);

        return back()->with('success', 'Anuntul global a fost publicat.');
    }

    public function update(Request $request, SystemAnnouncement $announcement): RedirectResponse
    {
        abort_unless(TenantContext::isPlatformAdmin($request->user()), 403);

        $announcement->update($this->validated($request));
        AccessAudit::log('platform.announcement.updated', $request->user(), $request, 'system_announcement', $announcement->id);

        return back()->with('success', 'Anuntul global a fost actualizat.');
    }

    public function destroy(Request $request, SystemAnnouncement $announcement): RedirectResponse
    {
        abort_unless(TenantContext::isPlatformAdmin($request->user()), 403);

        $announcement->update(['is_active' => false, 'ends_at' => now()]);
        AccessAudit::log('platform.announcement.deactivated', $request->user(), $request, 'system_announcement', $announcement->id);

        return back()->with('success', 'Anuntul global a fost dezactivat.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
            'level' => ['required', 'in:info,warning,critical'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }
}
