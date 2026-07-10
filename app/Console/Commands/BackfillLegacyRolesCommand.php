<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BackfillLegacyRolesCommand extends Command
{
    protected $signature = 'iam:backfill-legacy-roles {--apply : Actually assign the roles instead of just reporting}';

    protected $description = 'Assign a real role to any user with zero roles/permissions, so the legacy authorization bypass can be safely removed';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $legacyUsers = User::query()
            ->whereDoesntHave('roles')
            ->whereDoesntHave('permissions')
            ->orderBy('tenant_id')
            ->orderBy('created_at')
            ->get();

        if ($legacyUsers->isEmpty()) {
            $this->info('No users with zero roles/permissions found. Safe to remove the legacy bypass.');

            return self::SUCCESS;
        }

        $tenantAdminRole = Role::query()->whereNull('tenant_id')->firstWhere('name', 'tenant_admin');
        $dataEntryRole = Role::query()->whereNull('tenant_id')->firstWhere('name', 'data_entry');

        if (!$tenantAdminRole || !$dataEntryRole) {
            $this->error('Seeded roles "tenant_admin" / "data_entry" not found. Run the IAM seeder first.');

            return self::FAILURE;
        }

        $tenantsWithAdminAlready = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['tenant_admin', 'superadmin']))
            ->pluck('tenant_id')
            ->unique();

        $rows = [];
        $assignedAdminForTenant = [];

        /** @var Collection<int, User> $byTenant */
        foreach ($legacyUsers->groupBy('tenant_id') as $tenantId => $usersInTenant) {
            $tenantAlreadyHasAdmin = $tenantsWithAdminAlready->contains((int) $tenantId);

            foreach ($usersInTenant->values() as $index => $user) {
                $makeAdmin = !$tenantAlreadyHasAdmin && $index === 0;
                $role = $makeAdmin ? $tenantAdminRole : $dataEntryRole;

                $rows[] = [$user->id, $user->email, $tenantId, $role->name];

                if ($apply) {
                    $user->syncRoles([$role]);
                }
            }
        }

        $this->table(['User ID', 'Email', 'Tenant', 'Role to assign'], $rows);

        if ($apply) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->info(sprintf('Assigned roles to %d user(s).', count($rows)));
        } else {
            $this->comment('Dry run only - no changes were written. Re-run with --apply to assign these roles.');
        }

        return self::SUCCESS;
    }
}
