<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\ProjectRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class IamSeeder extends Seeder
{
    /**
     * Seed IAM baseline: tenant, permissions, and default roles.
     */
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Tenant implicit',
                'slug' => 'tenant-implicit',
                'billing_plan' => 'free',
                'status' => 'active',
                'module_flags' => [],
            ]
        );

        $permissions = $this->buildPermissionList();
        foreach ($permissions as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $roles = $this->rolePermissionMap();
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'tenant_id' => null,
            ]);
            $role->syncPermissions($rolePermissions);
        }

        foreach (ProjectRole::defaults() as $projectRole) {
            ProjectRole::query()->updateOrCreate(
                [
                    'tenant_id' => null,
                    'key' => $projectRole['key'],
                ],
                [
                    'name' => $projectRole['name'],
                    'description' => $projectRole['description'],
                ]
            );
        }

        User::query()->get()->each(function (User $user) use ($tenant): void {
            $isSuperadmin = in_array($user->email, config('platform.admin_emails', []), true);

            $updates = [
                'tenant_id' => $user->tenant_id ?: $tenant->id,
                'current_tenant_id' => $user->current_tenant_id ?: $user->tenant_id ?: $tenant->id,
                'is_superadmin' => $isSuperadmin,
            ];

            $user->forceFill($updates)->save();

            DB::table('tenant_users')->updateOrInsert(
                [
                    'tenant_id' => (int) $user->tenant_id,
                    'user_id' => (int) $user->id,
                ],
                [
                    'status' => 'active',
                    'joined_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $role = Role::query()->whereNull('tenant_id')->firstWhere('name', $isSuperadmin ? 'superadmin' : 'tenant_admin');
            if ($role) {
                $user->syncRoles([$role]);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array<int, string>
     */
    private function buildPermissionList(): array
    {
        $modules = [
            'quotes',
            'projects',
            'tasks',
            'calendar',
            'documents',
            'finance',
            'ai_tools',
            'company_settings',
            'users',
            'reports',
            'contractors',
            'equipment',
        ];

        $actions = ['view', 'create', 'edit', 'delete', 'export', 'approve'];

        $permissions = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        $permissions[] = 'finance.view_limited';
        $permissions[] = 'roles.manage';
        $permissions[] = 'tenants.manage';
        $permissions[] = 'quotes.internal_approve';

        return array_values(array_unique($permissions));
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rolePermissionMap(): array
    {
        $allPermissions = Permission::query()->pluck('name')->all();

        return [
            'superadmin' => $allPermissions,
            'tenant_admin' => Arr::where($allPermissions, fn (string $name) => !str_starts_with($name, 'tenants.')),
            'data_entry' => [
                'projects.view',
                'tasks.view', 'tasks.create', 'tasks.edit',
                'documents.view', 'documents.create', 'documents.edit',
                'equipment.view', 'equipment.create', 'equipment.edit',
            ],
            'quote_specialist' => [
                'quotes.view', 'quotes.create', 'quotes.edit', 'quotes.export',
                'projects.view',
                'finance.view_limited',
            ],
            'site_manager' => [
                'projects.view', 'projects.create', 'projects.edit',
                'tasks.view', 'tasks.create', 'tasks.edit',
                'calendar.view', 'calendar.create', 'calendar.edit',
                'quotes.view',
                'reports.view', 'reports.create', 'reports.edit',
            ],
            'finance' => [
                'finance.view', 'finance.create', 'finance.edit', 'finance.export', 'finance.approve',
                'reports.view', 'reports.export',
                'projects.view',
                'quotes.view',
                'documents.view',
            ],
            'auditor' => [
                'quotes.view',
                'projects.view',
                'tasks.view',
                'calendar.view',
                'documents.view',
                'finance.view',
                'reports.view',
                'contractors.view',
                'equipment.view',
            ],
            'client_portal' => [
                'projects.view',
                'documents.view',
                'reports.view',
            ],
            'subcontractor_portal' => [
                'projects.view',
                'tasks.view',
                'calendar.view',
                'documents.view',
            ],
        ];
    }
}
