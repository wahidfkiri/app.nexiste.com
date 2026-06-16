<?php

namespace Vendor\Rbac\Services;

use App\Models\TenantUserMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantRoleService
{
    public function ensureTenantRoles(int $tenantId): Collection
    {
        if ($tenantId <= 0) {
            return new Collection();
        }

        $this->ensureGlobalPermissions();

        $roles = new Collection();
        $defaults = config('rbac.default_roles', []);

        foreach ($defaults as $name => $definition) {
            $role = Role::query()
                ->where('tenant_id', $tenantId)
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            if (!$role) {
                $role = Role::query()->create([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'guard_name' => 'web',
                    'label' => $definition['label'] ?? ucfirst($name),
                    'description' => $definition['description'] ?? null,
                    'color' => $definition['color'] ?? '#64748b',
                    'is_system' => $name === 'owner',
                    'is_active' => true,
                ]);

                $this->syncDefaultPermissions($role);
            } else {
                $this->syncDefaultPermissions($role, true);
            }

            $roles->push($role->fresh(['permissions']));
        }

        $this->clearPermissionCache();

        return $roles;
    }

    public function findTenantRoleById(int $tenantId, int $roleId): ?Role
    {
        $this->ensureTenantRoles($tenantId);

        return Role::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $roleId)
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->first();
    }

    public function findTenantRoleByName(int $tenantId, string $name): ?Role
    {
        $this->ensureTenantRoles($tenantId);

        return Role::query()
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->first();
    }

    public function resolveTenantRole(int $tenantId, int|string|Role $role): Role
    {
        if ($role instanceof Role) {
            if ((int) $role->tenant_id !== $tenantId || !$role->is_active) {
                throw new \RuntimeException(__('rbac::rbac.errors.role_not_active_tenant'));
            }

            return $role;
        }

        $resolved = is_numeric($role)
            ? $this->findTenantRoleById($tenantId, (int) $role)
            : $this->findTenantRoleByName($tenantId, (string) $role);

        if (!$resolved) {
            throw new \RuntimeException(__('rbac::rbac.errors.role_not_found_tenant'));
        }

        return $resolved;
    }

    public function syncUserRole(User $user, int $tenantId, int|string|Role $role, array $membershipOverrides = []): Role
    {
        $tenantRole = $this->resolveTenantRole($tenantId, $role);

        DB::transaction(function () use ($user, $tenantId, $tenantRole, $membershipOverrides): void {
            $membership = TenantUserMembership::query()->firstOrNew([
                'user_id' => (int) $user->id,
                'tenant_id' => $tenantId,
            ]);

            $membership->fill(array_merge([
                'role_id' => (int) $tenantRole->id,
                'role_in_tenant' => (string) $tenantRole->name,
                'is_tenant_owner' => $tenantRole->name === 'owner',
                'status' => 'active',
                'joined_at' => $membership->joined_at ?: now(),
            ], $membershipOverrides));
            $membership->save();

            $tenantRoleIds = Role::query()
                ->where('tenant_id', $tenantId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($tenantRoleIds !== []) {
                $user->roles()->detach($tenantRoleIds);
            }

            $user->roles()->syncWithoutDetaching([(int) $tenantRole->id]);

            if (!(int) $user->getOriginal('tenant_id') || (int) $user->getOriginal('tenant_id') === $tenantId) {
                $user->forceFill([
                    'tenant_id' => $tenantId,
                    'role_in_tenant' => (string) $tenantRole->name,
                    'is_tenant_owner' => (bool) $membership->is_tenant_owner,
                    'status' => (string) $membership->status,
                    'is_active' => (string) $membership->status === 'active',
                ])->save();
            }
        });

        $this->clearPermissionCache();

        return $tenantRole->fresh(['permissions']);
    }

    public function tenantRoleForUser(User $user, int $tenantId): ?Role
    {
        $membership = $user->tenantMemberships()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();

        if (!$membership) {
            return null;
        }

        if ($membership->role_id) {
            return $this->findTenantRoleById($tenantId, (int) $membership->role_id);
        }

        if ($membership->role_in_tenant) {
            return $this->findTenantRoleByName($tenantId, (string) $membership->role_in_tenant);
        }

        return null;
    }

    public function ensureGlobalPermissions(): void
    {
        $groups = config('rbac.permission_groups', []);

        foreach ($groups as $groupKey => $groupDefinition) {
            foreach (($groupDefinition['permissions'] ?? []) as $name => $label) {
                $permission = Permission::query()->firstOrNew([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);

                if (Schema::hasColumn('permissions', 'tenant_id')) {
                    $permission->tenant_id = null;
                }

                if (Schema::hasColumn('permissions', 'label')) {
                    $permission->label = $label;
                }

                if (Schema::hasColumn('permissions', 'group')) {
                    $permission->group = $groupKey;
                }

                if (Schema::hasColumn('permissions', 'description') && empty($permission->description)) {
                    $permission->description = $groupDefinition['label'] ?? $groupKey;
                }

                $permission->save();
            }
        }
    }

    private function syncDefaultPermissions(Role $role, bool $onlyMissing = false): void
    {
        $permissionNames = config("rbac.default_role_permissions.{$role->name}", []);

        if ($permissionNames === ['*']) {
            $permissionNames = Permission::query()
                ->whereNull('tenant_id')
                ->pluck('name')
                ->all();
        }

        $platformOnlyPermissions = config('rbac.platform_only_permissions', []);
        if ($platformOnlyPermissions !== []) {
            $permissionNames = array_values(array_diff($permissionNames, $platformOnlyPermissions));
        }

        $validPermissionNames = Permission::query()
            ->whereNull('tenant_id')
            ->whereIn('name', $permissionNames)
            ->pluck('name')
            ->all();

        if ($onlyMissing) {
            $role->givePermissionTo($validPermissionNames);
            return;
        }

        $role->syncPermissions($validPermissionNames);
    }

    private function clearPermissionCache(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
