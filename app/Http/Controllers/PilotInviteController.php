<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePilotInviteRequest;
use App\Models\PilotInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PilotInviteController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        $invites = PilotInvite::query()
            ->where('tenant_id', 1)
            ->with('owner:id,name,email')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('PilotInvites/Index', [
            'invites' => $invites,
            'filters' => [
                'status' => $status,
            ],
            'statusOptions' => [
                'invited',
                'contacted',
                'demo_scheduled',
                'trial_started',
                'closed_won',
                'closed_lost',
            ],
        ]);
    }

    public function store(StorePilotInviteRequest $request): RedirectResponse
    {
        PilotInvite::create([
            ...$request->validated(),
            'tenant_id' => 1,
            'owner_id' => $request->user()?->id,
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        return back()->with('success', 'Invitatia pilot a fost adaugata.');
    }

    public function updateStatus(Request $request, PilotInvite $pilotInvite): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:invited,contacted,demo_scheduled,trial_started,closed_won,closed_lost'],
            'demo_scheduled_at' => ['nullable', 'date'],
        ]);

        $updates = [
            'status' => $validated['status'],
        ];

        if ($validated['status'] === 'demo_scheduled' && !empty($validated['demo_scheduled_at'])) {
            $updates['demo_scheduled_at'] = $validated['demo_scheduled_at'];
        }

        if ($validated['status'] !== 'demo_scheduled') {
            $updates['demo_scheduled_at'] = null;
        }

        $pilotInvite->update($updates);

        return back()->with('success', 'Status invitatie actualizat.');
    }
}
