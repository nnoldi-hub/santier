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
        $customization = $request->string('customization')->toString();
        $sort = $request->string('sort')->toString();
        if (! in_array($sort, ['users_desc', 'latest'], true)) {
            $sort = 'users_desc';
        }
        $scopeLabels = $this->scopeLabels();
        $selectedScopeLabel = $scopeLabels[$customization] ?? null;

        $invites = PilotInvite::query()
            ->where('tenant_id', 1)
            ->with('owner:id,name,email')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($selectedScopeLabel, fn ($query) => $query->where('notes', 'like', '%Personalizare dorita: '.$selectedScopeLabel.'%'))
            ->latest('id')
            ->paginate(20)
            ->through(function (PilotInvite $invite) {
                $qualification = $this->extractSalesQualification($invite->notes);

                return [
                    'id' => $invite->id,
                    'company_name' => $invite->company_name,
                    'segment' => $invite->segment,
                    'contact_name' => $invite->contact_name,
                    'contact_email' => $invite->contact_email,
                    'contact_phone' => $invite->contact_phone,
                    'status' => $invite->status,
                    'invited_at' => $invite->invited_at,
                    'demo_scheduled_at' => $invite->demo_scheduled_at,
                    'notes' => $invite->notes,
                    'owner' => $invite->owner,
                    'estimated_users' => $qualification['estimated_users'],
                    'customization_scope_label' => $qualification['customization_scope_label'],
                ];
            })
            ->withQueryString();

        return Inertia::render('PilotInvites/Index', [
            'invites' => $invites,
            'filters' => [
                'status' => $status,
                'customization' => $customization,
                'sort' => $sort,
            ],
            'statusOptions' => [
                'invited',
                'contacted',
                'demo_scheduled',
                'trial_started',
                'closed_won',
                'closed_lost',
            ],
            'customizationOptions' => $scopeLabels,
        ]);
    }

    public function store(StorePilotInviteRequest $request): RedirectResponse
    {
        $this->persistInvite($request, $request->user()?->id);

        return back()->with('success', 'Invitatia pilot a fost adaugata.');
    }

    public function storePublic(StorePilotInviteRequest $request): RedirectResponse
    {
        $this->persistInvite($request, null);

        return back()->with('success', 'Solicitarea ta a fost trimisa. Te contactam in cel mai scurt timp.');
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

    private function persistInvite(StorePilotInviteRequest $request, ?int $ownerId): PilotInvite
    {
        $validated = $request->validated();

        return PilotInvite::create([
            'company_name' => $validated['company_name'],
            'segment' => $validated['segment'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'] ?? null,
            'notes' => $this->buildSalesNotes($validated),
            'tenant_id' => 1,
            'owner_id' => $ownerId,
            'status' => 'invited',
            'invited_at' => now(),
        ]);
    }

    private function buildSalesNotes(array $validated): ?string
    {
        $scopeLabels = $this->scopeLabels();

        $lines = [
            'Lead qualification',
            'Utilizatori estimati: '.($validated['estimated_users'] ?? '-'),
            'Personalizare dorita: '.($scopeLabels[$validated['customization_scope']] ?? (string) ($validated['customization_scope'] ?? '-')),
        ];

        $existingNotes = trim((string) ($validated['notes'] ?? ''));
        if ($existingNotes !== '') {
            $lines[] = 'Note client: '.$existingNotes;
        }

        return implode("\n", $lines);
    }

    private function scopeLabels(): array
    {
        return [
            'branding' => 'Branding documente',
            'template' => 'Template documente',
            'approvals' => 'Flux aprobari',
            'white_label' => 'White-label',
            'custom_domain' => 'Domeniu propriu',
            'full_enterprise' => 'Pachet enterprise complet',
        ];
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
}
