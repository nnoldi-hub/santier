<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePilotInviteRequest;
use App\Mail\PilotInvitationMail;
use App\Models\CommercialAction;
use App\Models\CommercialTask;
use App\Models\PilotInvite;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
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
        $reminderToday = $request->boolean('reminder_today');
        $noNextStep = $request->boolean('no_next_step');
        $stagnant = $request->boolean('stagnant');
        $stagnantThreshold = now()->subDays((int) config('pilot_invites.stagnant_days', 14));

        $invites = PilotInvite::query()
            ->where('tenant_id', $tenantId)
            ->with(['owner:id,name,email', 'commercialTasks', 'commercialActions.actor:id,name'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($commercialStage !== '', fn ($query) => $query->where('commercial_stage', $commercialStage))
            ->when($selectedScopeLabel, fn ($query) => $query->where('notes', 'like', '%Personalizare dorita: '.$selectedScopeLabel.'%'))
            ->when($reminderToday, fn ($query) => $query->whereDate('follow_up_at', today()))
            ->when($noNextStep, fn ($query) => $query->whereNull('next_step'))
            ->when($stagnant, fn ($query) => $query
                ->whereIn('status', PilotInvite::ACTIVE_STATUSES)
                ->where(fn ($inner) => $inner
                    ->whereNull('last_contacted_at')
                    ->orWhere('last_contacted_at', '<', $stagnantThreshold)))
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
                    'commercial_task' => $this->resolveCommercialTaskSummary($invite->commercialTasks),
                    'commercial_actions' => $invite->commercialActions->take(3)->map(fn (CommercialAction $action) => [
                        'id' => $action->id,
                        'action_type' => $action->action_type,
                        'notes' => $action->notes,
                        'actor_name' => $action->actor?->name,
                        'created_at' => optional($action->created_at)->toDateTimeString(),
                    ])->values(),
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
                'reminder_today' => $reminderToday,
                'no_next_step' => $noNextStep,
                'stagnant' => $stagnant,
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
            'actionTypes' => CommercialAction::$typeLabels,
        ]);
    }

    public function store(StorePilotInviteRequest $request): RedirectResponse
    {
        $invite = $this->persistInvite($request, $request->user()?->id);
        $this->syncCommercialTaskAutomation($invite, (int) ($request->user()?->id ?? 0));

        return back()->with('success', 'Invitatia pilot a fost adaugata.');
    }

    public function storePublic(StorePilotInviteRequest $request): RedirectResponse
    {
        $invite = $this->persistInvite($request, null);
        $this->syncCommercialTaskAutomation($invite, 0);

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
        $this->syncCommercialTaskAutomation($pilotInvite->fresh(['owner', 'commercialTasks']), (int) ($request->user()?->id ?? 0));

        return back()->with('success', 'Status invitatie actualizat.');
    }

    public function storeAction(Request $request, PilotInvite $pilotInvite): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $pilotInvite->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'action_type' => ['required', 'in:' . implode(',', array_keys(CommercialAction::$typeLabels))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        CommercialAction::create([
            'tenant_id' => $tenantId,
            'pilot_invite_id' => $pilotInvite->id,
            'actor_id' => $request->user()?->id,
            'action_type' => $validated['action_type'],
            'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
        ]);

        $pilotInvite->update(['last_contacted_at' => now()]);

        return back()->with('success', 'Actiunea a fost inregistrata.');
    }

    public function sendInvitation(Request $request, PilotInvite $pilotInvite): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $pilotInvite->tenant_id === $tenantId, 404);
        abort_if(trim((string) $pilotInvite->contact_email) === '', 422, 'Invitatia nu are un email de contact.');

        $sender = $request->user();
        $owner = $pilotInvite->owner;
        $senderName = $sender?->name ?? $owner?->name ?? 'Echipa Modulia';
        $replyToEmail = $owner?->email ?? $sender?->email;
        $replyToName = $owner?->name ?? $senderName;

        Mail::to($pilotInvite->contact_email)
            ->send(new PilotInvitationMail($pilotInvite, $senderName, $replyToEmail, $replyToName));

        CommercialAction::create([
            'tenant_id' => $tenantId,
            'pilot_invite_id' => $pilotInvite->id,
            'actor_id' => $sender?->id,
            'action_type' => 'email',
            'notes' => 'Invitatie initiala trimisa pe email catre ' . $pilotInvite->contact_email,
        ]);

        $pilotInvite->update(['last_contacted_at' => now()]);

        return back()->with('success', 'Invitatia a fost trimisa catre ' . $pilotInvite->contact_email . '.');
    }

    public function markHandoff(Request $request, PilotInvite $pilotInvite): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $pilotInvite->tenant_id === $tenantId, 404);

        abort_if($pilotInvite->status !== 'closed_won' || $pilotInvite->onboarding_handoff_at !== null, 422);

        $pilotInvite->update(['onboarding_handoff_at' => now()]);

        return back()->with('success', 'Handoff catre onboarding marcat.');
    }

    private function resolveCommercialTaskSummary(Collection $tasks): ?array
    {
        /** @var CommercialTask|null $task */
        $task = $tasks->first(fn (CommercialTask $item) => in_array($item->status, ['todo', 'in_progress'], true));

        if (! $task instanceof CommercialTask) {
            return null;
        }

        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status,
            'priority' => $task->priority,
            'due_at' => optional($task->due_at)->toDateTimeString(),
        ];
    }

    private function syncCommercialTaskAutomation(PilotInvite $invite, int $actorId): void
    {
        $openStatuses = ['todo', 'in_progress'];

        $openTask = CommercialTask::query()
            ->where('pilot_invite_id', $invite->id)
            ->whereIn('status', $openStatuses)
            ->latest('id')
            ->first();

        if (! in_array((string) $invite->status, PilotInvite::ACTIVE_STATUSES, true)) {
            if ($openTask instanceof CommercialTask) {
                $openTask->update([
                    'status' => 'cancelled',
                    'completed_at' => now(),
                ]);
            }

            return;
        }

        $dueAt = $invite->follow_up_at
            ?? $invite->demo_scheduled_at
            ?? now()->addDay();

        $priority = in_array((string) $invite->status, ['demo_scheduled', 'trial_started'], true)
            ? 'high'
            : 'medium';

        $payload = [
            'tenant_id' => (int) $invite->tenant_id,
            'assigned_to' => $invite->owner_id,
            'created_by' => $actorId > 0 ? $actorId : null,
            'title' => 'Follow-up comercial: ' . $invite->company_name,
            'description' => $this->buildCommercialTaskDescription($invite),
            'status' => 'todo',
            'priority' => $priority,
            'due_at' => $dueAt,
            'completed_at' => null,
            'automated' => true,
        ];

        if ($openTask instanceof CommercialTask) {
            $openTask->update($payload);

            return;
        }

        $task = CommercialTask::create([
            ...$payload,
            'pilot_invite_id' => $invite->id,
        ]);

        $recipient = $invite->owner;

        if (! $recipient instanceof User) {
            return;
        }

        $recipient->notify(new OperationalReminderNotification(
            event: 'commercial_follow_up',
            title: 'Task comercial nou',
            message: sprintf('Lead-ul %s necesita follow-up pana la %s.', $invite->company_name, $task->due_at?->format('Y-m-d H:i') ?? '-'),
            entityType: 'pilot_invite',
            entityId: (int) $invite->id,
            projectId: null,
            projectName: null,
            url: route('pilot-invites.index'),
            severity: $priority === 'high' ? 'high' : 'medium',
        ));
    }

    private function buildCommercialTaskDescription(PilotInvite $invite): string
    {
        $lines = [
            'Lead: ' . $invite->company_name,
            'Status curent: ' . (string) $invite->status,
            'Etapa comerciala: ' . (string) $this->resolveCommercialStage($invite),
            'Contact: ' . ($invite->contact_name ?: '-') . ' / ' . ($invite->contact_email ?: '-'),
            'Urmator pas: ' . ($invite->next_step ?: 'Actualizeaza urmatorul pas comercial.'),
        ];

        return implode("\n", $lines);
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
