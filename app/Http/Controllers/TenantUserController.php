<?php

namespace App\Http\Controllers;

use App\Models\TenantUser;
use App\Models\User;
use App\Support\AccessAudit;
use App\Support\IamLabels;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class TenantUserController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->authorizeUserManagement($user);

        $tenantId = TenantContext::id($user);

        $filters = $request->validate([
            'status' => ['nullable', 'in:all,active,suspended'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $status = (string) ($filters['status'] ?? 'all');
        $roleId = (int) ($filters['role_id'] ?? 0);
        $search = trim((string) ($filters['search'] ?? ''));

        $membershipsQuery = TenantUser::query()
            ->where('tenant_id', $tenantId)
            ->with(['user.roles'])
            ->orderByDesc('id');

        if ($status !== 'all') {
            $membershipsQuery->where('status', $status);
        }

        if ($roleId > 0) {
            $membershipsQuery->whereHas('user.roles', function ($query) use ($roleId): void {
                $query->where('roles.id', $roleId);
            });
        }

        if ($search !== '') {
            $escapedSearch = addcslashes($search, '%_');
            $membershipsQuery->where(function ($query) use ($escapedSearch): void {
                $query->where('department', 'like', '%' . $escapedSearch . '%')
                    ->orWhereHas('user', function ($userQuery) use ($escapedSearch): void {
                        $userQuery->where('name', 'like', '%' . $escapedSearch . '%')
                            ->orWhere('email', 'like', '%' . $escapedSearch . '%');
                    });
            });
        }

        $memberships = $membershipsQuery->get()
            ->map(function (TenantUser $membership) {
                $member = $membership->user;

                return [
                    'membership_id' => $membership->id,
                    'id' => $member?->id,
                    'name' => $member?->name,
                    'email' => $member?->email,
                    'status' => $membership->status,
                    'department' => $membership->department,
                    'joined_at' => optional($membership->joined_at)->toDateString(),
                    'roles' => $member ? $member->roles->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'label' => IamLabels::roleLabel((string) $role->name),
                    ])->values() : [],
                ];
            })
            ->values();

        $roles = Role::query()
            ->where(function ($query) use ($tenantId) {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderByRaw('CASE WHEN tenant_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get(['id', 'name', 'tenant_id'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => IamLabels::roleLabel((string) $role->name),
                'is_global' => $role->tenant_id === null,
            ])
            ->values();

        return Inertia::render('Account/Users', [
            'members' => $memberships,
            'roles' => $roles,
            'filters' => [
                'status' => $status,
                'role_id' => $roleId > 0 ? $roleId : '',
                'search' => $search,
            ],
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $this->authorizeUserManagement($actor);

        $tenantId = TenantContext::id($actor);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::query()->findOrFail((int) $data['role_id']);
        abort_unless($role->tenant_id === null || (int) $role->tenant_id === $tenantId, 422, 'Rolul ales nu este disponibil pentru tenantul curent.');

        DB::transaction(function () use ($data, $tenantId, $actor, $role): void {
            $email = strtolower(trim((string) $data['email']));
            $displayName = trim((string) ($data['name'] ?? ''));

            $member = User::query()->firstWhere('email', $email);
            if (!$member) {
                $member = User::query()->create([
                    'name' => $displayName !== '' ? $displayName : Str::before($email, '@'),
                    'email' => $email,
                    'password' => Hash::make(Str::password(20)),
                    'tenant_id' => $tenantId,
                    'current_tenant_id' => $tenantId,
                ]);
            }

            TenantUser::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'user_id' => $member->id],
                [
                    'department' => $data['department'] ?? null,
                    'status' => 'active',
                    'invited_by' => $actor->id,
                    'joined_at' => now(),
                ]
            );

            $member->syncRoles([$role]);

            AccessAudit::log(
                action: 'iam.user.invited',
                actor: $actor,
                request: request(),
                resourceType: 'user',
                resourceId: (int) $member->id,
                metadata: [
                    'email' => $email,
                    'role' => $role->name,
                    'department' => $data['department'] ?? null,
                ]
            );
        });

        return back()->with('success', 'Utilizatorul a fost adaugat in firma si i s-a atribuit rolul selectat.');
    }

    public function updateStatus(Request $request, TenantUser $membership): RedirectResponse
    {
        $actor = $request->user();
        $this->authorizeUserManagement($actor);

        $tenantId = TenantContext::id($actor);
        abort_unless((int) $membership->tenant_id === $tenantId, 404);

        $data = $request->validate([
            'status' => ['required', 'in:active,suspended'],
        ]);

        $membership->update([
            'status' => $data['status'],
        ]);

        $membership->loadMissing('user');
        AccessAudit::log(
            action: 'iam.user.status_changed',
            actor: $actor,
            request: $request,
            resourceType: 'user',
            resourceId: (int) ($membership->user?->id ?? 0),
            metadata: [
                'status' => $data['status'],
                'membership_id' => $membership->id,
            ]
        );

        return back()->with('success', $data['status'] === 'active' ? 'Utilizator reactivat.' : 'Utilizator suspendat.');
    }

    public function updateRole(Request $request, TenantUser $membership): RedirectResponse
    {
        $actor = $request->user();
        $this->authorizeUserManagement($actor);

        $tenantId = TenantContext::id($actor);
        abort_unless((int) $membership->tenant_id === $tenantId, 404);

        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::query()->findOrFail((int) $data['role_id']);
        abort_unless($role->tenant_id === null || (int) $role->tenant_id === $tenantId, 422, 'Rolul ales nu este disponibil pentru tenantul curent.');

        $membership->loadMissing('user');
        if ($membership->user) {
            $membership->user->syncRoles([$role]);
        }

        AccessAudit::log(
            action: 'iam.user.role_changed',
            actor: $actor,
            request: $request,
            resourceType: 'user',
            resourceId: (int) ($membership->user?->id ?? 0),
            metadata: [
                'role' => $role->name,
                'membership_id' => $membership->id,
            ]
        );

        return back()->with('success', 'Rolul utilizatorului a fost actualizat.');
    }

    private function authorizeUserManagement(User $user): void
    {
        if (TenantContext::isSuperadmin($user) || $user->hasRole('tenant_admin')) {
            return;
        }

        abort_unless($user->can('users.view') || $user->can('users.edit'), 403);
    }
}
