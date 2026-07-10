<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AccessAudit;
use App\Support\IamLabels;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantRoleController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->authorizeRoleManagement($user);

        $tenantId = TenantContext::id($user);

        $permissions = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => IamLabels::permissionLabel((string) $permission->name),
            ])
            ->values();

        $roles = Role::query()
            ->where(function ($query) use ($tenantId) {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->with('permissions:id,name')
            ->orderByRaw('CASE WHEN tenant_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get(['id', 'name', 'tenant_id'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => IamLabels::roleLabel((string) $role->name),
                'is_global' => $role->tenant_id === null,
                'permissions' => $role->permissions->map(fn (Permission $permission) => [
                    'name' => $permission->name,
                    'label' => IamLabels::permissionLabel((string) $permission->name),
                ])->values(),
            ])
            ->values();

        return Inertia::render('Account/Roles', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeRoleManagement($user);

        $tenantId = TenantContext::id($user);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('guard_name', 'web')),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::query()->create([
            'name' => strtolower(trim((string) $data['name'])),
            'guard_name' => 'web',
            'tenant_id' => $tenantId,
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        AccessAudit::log(
            action: 'iam.role.created',
            actor: $user,
            request: $request,
            resourceType: 'role',
            resourceId: (int) $role->id,
            metadata: [
                'name' => $role->name,
                'permissions' => $data['permissions'] ?? [],
            ]
        );

        return back()->with('success', 'Rol custom creat.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeRoleManagement($user);

        $tenantId = TenantContext::id($user);
        abort_unless((int) $role->tenant_id === $tenantId, 404);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')
                    ->ignore($role->id)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('guard_name', 'web')),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update([
            'name' => strtolower(trim((string) $data['name'])),
        ]);
        $role->syncPermissions($data['permissions'] ?? []);

        AccessAudit::log(
            action: 'iam.role.updated',
            actor: $user,
            request: $request,
            resourceType: 'role',
            resourceId: (int) $role->id,
            metadata: [
                'name' => $role->name,
                'permissions' => $data['permissions'] ?? [],
            ]
        );

        return back()->with('success', 'Rol actualizat.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeRoleManagement($user);

        $tenantId = TenantContext::id($user);
        abort_unless((int) $role->tenant_id === $tenantId, 404);

        $roleName = $role->name;
        $roleId = (int) $role->id;

        $role->delete();

        AccessAudit::log(
            action: 'iam.role.deleted',
            actor: $user,
            request: $request,
            resourceType: 'role',
            resourceId: $roleId,
            metadata: [
                'name' => $roleName,
            ]
        );

        return back()->with('success', 'Rol sters.');
    }

    private function authorizeRoleManagement(User $user): void
    {
        if (TenantContext::isSuperadmin($user) || $user->hasRole('tenant_admin')) {
            return;
        }

        abort_unless($user->can('roles.manage') || $user->can('users.edit'), 403);
    }
}
