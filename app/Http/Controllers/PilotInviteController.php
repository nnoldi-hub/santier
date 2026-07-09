<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePilotInviteRequest;
use App\Models\PilotInvite;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PilotInviteController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());
        $status = $request->string('status')->toString();
        $commercialStage = $request->string('commercial_stage')->toString();
        $customization = $request->string('customization')->toString();
        $sort = $request->string('sort')->toString();
        if (! in_array($sort, ['users_desc', 'latest'], true)) {
            $sort = 'users_desc';
        }
        if (! array_key_exists($commercialStage, $this->stageLabels()) && $commercialStage !== '') {
            $commercialStage = '';
        }
        $scopeLabels = $this->scopeLabels();
        $selectedScopeLabel = $scopeLabels[$customization] ?? null;

        $invites = PilotInvite::query()
            ->where('tenant_id', $tenantId)
            ->with('owner:id,name,email')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($commercialStage !== '', fn ($query) => $query->where('commercial_stage', $commercialStage))
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
                    'commercial_stage' => $this->resolveCommercialStage($invite),
                    'invited_at' => $invite->invited_at,
                    'demo_scheduled_at' => $invite->demo_scheduled_at,
                    'follow_up_at' => $invite->follow_up_at,
                    'last_contacted_at' => $invite->last_contacted_at,
                    'next_step' => $invite->next_step,
                    'notes' => $invite->notes,
                    'owner' => $invite->owner,
                    'estimated_users' => $qualification['estimated_users'],
                    'customization_scope_label' => $qualification['customization_scope_label'],
                ];
            })
            ->withQueryString();

        return Inertia::render('PilotInvites/Index', [
            'invites' => $invites,
            'owners' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'filters' => [
                'status' => $status,
                'commercial_stage' => $commercialStage,
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
            'stageOptions' => $this->stageLabels(),
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
            'commercial_stage' => ['nullable', 'string', 'in:prospecting,contacted,follow_up,demo,trial,negotiation,won,lost'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'demo_scheduled_at' => ['nullable', 'date'],
            'follow_up_at' => ['nullable', 'date'],
            'next_step' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $commercialStage = $this->normalizeCommercialStage(
            $validated['status'],
            $validated['commercial_stage'] ?? $pilotInvite->commercial_stage
        );

        $updates = [
            'status' => $validated['status'],
            'commercial_stage' => $commercialStage,
            'owner_id' => $validated['owner_id'] ?? $pilotInvite->owner_id,
            'follow_up_at' => $validated['follow_up_at'] ?? null,
            'next_step' => trim((string) ($validated['next_step'] ?? '')) ?: null,
            'notes' => array_key_exists('notes', $validated) ? trim((string) $validated['notes']) ?: null : $pilotInvite->notes,
        ];

        if ($validated['status'] === 'demo_scheduled' && !empty($validated['demo_scheduled_at'])) {
            $updates['demo_scheduled_at'] = $validated['demo_scheduled_at'];
        }

        if ($validated['status'] !== 'demo_scheduled') {
            $updates['demo_scheduled_at'] = null;
        }

        if ($validated['status'] === 'contacted') {
            $updates['last_contacted_at'] = now();
        }

        $pilotInvite->update($updates);

        return back()->with('success', 'Status invitatie actualizat.');
    }

    private function persistInvite(StorePilotInviteRequest $request, ?int $ownerId): PilotInvite
    {
        $tenantId = TenantContext::id($request->user());
        $validated = $request->validated();

        return PilotInvite::create([
            'company_name' => $validated['company_name'],
            'segment' => $validated['segment'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'] ?? null,
            'notes' => $this->buildSalesNotes($validated),
            'tenant_id' => $tenantId,
            'owner_id' => $validated['owner_id'] ?? $ownerId,
            'status' => 'invited',
            'commercial_stage' => 'prospecting',
            'invited_at' => now(),
            'follow_up_at' => $validated['follow_up_at'] ?? null,
            'next_step' => trim((string) ($validated['next_step'] ?? '')) ?: null,
        ]);
    }

    private function stageLabels(): array
    {
        return [
            'prospecting' => 'Prospectare',
            'contacted' => 'Contactat',
            'follow_up' => 'Follow-up',
            'demo' => 'Demo',
            'trial' => 'Trial',
            'negotiation' => 'Negociere',
            'won' => 'Castigat',
            'lost' => 'Pierdut',
        ];
    }

    private function resolveCommercialStage(PilotInvite $invite): string
    {
        return $invite->commercial_stage ?: $this->normalizeCommercialStage($invite->status, null);
    }

    private function normalizeCommercialStage(string $status, ?string $requestedStage): string
    {
        $fallbackByStatus = [
            'invited' => 'prospecting',
            'contacted' => 'contacted',
            'demo_scheduled' => 'demo',
            'trial_started' => 'trial',
            'closed_won' => 'won',
            'closed_lost' => 'lost',
        ];

        $allowedByStatus = [
            'invited' => ['prospecting'],
            'contacted' => ['contacted', 'follow_up'],
            'demo_scheduled' => ['demo', 'follow_up'],
            'trial_started' => ['trial', 'negotiation'],
            'closed_won' => ['won'],
            'closed_lost' => ['lost'],
        ];

        $target = $requestedStage ?: ($fallbackByStatus[$status] ?? 'prospecting');

        if (! in_array($target, $allowedByStatus[$status] ?? ['prospecting'], true)) {
            return $fallbackByStatus[$status] ?? 'prospecting';
        }

        return $target;
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
