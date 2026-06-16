<?php

namespace Vendor\Rbac\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Vendor\Rbac\Services\TenantRoleService;

class RbacRepository
{
    public function __construct(protected TenantRoleService $tenantRoleService)
    {
    }

    public function getRolesForTenant(int $tenantId): Collection
    {
        $this->tenantRoleService->ensureTenantRoles($tenantId);

        return Role::query()
            ->where('tenant_id', $tenantId)
            ->withCount('permissions')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();
    }

    public function getFilteredRoles(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $this->tenantRoleService->ensureTenantRoles($tenantId);

        $query = Role::query()
            ->where('tenant_id', $tenantId)
            ->withCount('permissions')
            ->with('permissions');

        if (!empty($filters['search'])) {
            $query->where(function ($query) use ($filters): void {
                $query->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('label', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findRole(int $id): ?Role
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $this->tenantRoleService->ensureTenantRoles($tenantId);

        return Role::query()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with('permissions')
            ->first();
    }

    public function createRole(array $data): Role
    {
        return Role::query()->create([
            'name' => $data['name'],
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#64748b',
            'guard_name' => 'web',
            'tenant_id' => (int) Auth::user()->tenant_id,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    public function updateRole(Role $role, array $data): Role
    {
        $role->update(array_filter([
            'label' => $data['label'] ?? null,
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($value) => $value !== null));

        return $role->fresh(['permissions']);
    }

    public function deleteRole(Role $role): bool
    {
        if ((bool) $role->is_system || (int) $role->tenant_id <= 0 || in_array($role->name, config('rbac.system_roles', []), true)) {
            throw new \RuntimeException(__('rbac::rbac.errors.system_role_delete_forbidden'));
        }

        if (array_key_exists((string) $role->name, config('rbac.default_roles', []))) {
            throw new \RuntimeException(__('rbac::rbac.errors.default_role_delete_forbidden'));
        }

        if (
            \App\Models\TenantUserMembership::query()
                ->where('role_id', (int) $role->id)
                ->where('status', 'active')
                ->exists()
        ) {
            throw new \RuntimeException(__('rbac::rbac.errors.role_assigned_users'));
        }

        return (bool) $role->delete();
    }

    public function syncRolePermissions(Role $role, array $permissionNames): Role
    {
        $validNames = Permission::query()
            ->whereNull('tenant_id')
            ->whereIn('name', $permissionNames)
            ->pluck('name')
            ->all();

        $role->syncPermissions($validNames);

        return $role->fresh(['permissions']);
    }

    public function getAllPermissions(): Collection
    {
        $this->tenantRoleService->ensureGlobalPermissions();

        return Permission::query()
            ->whereNull('tenant_id')
            ->orderBy('group')
            ->orderBy('name')
            ->get();
    }

    public function getPermissionsGrouped(): array
    {
        $permissions = $this->getAllPermissions();
        $groups = config('rbac.permission_groups', []);
        $result = [];

        foreach ($groups as $groupKey => $groupDefinition) {
            $result[$groupKey] = [
                'label' => $groupDefinition['label'],
                'icon' => $groupDefinition['icon'],
                'permissions' => [],
            ];

            foreach (array_keys($groupDefinition['permissions']) as $permissionName) {
                $permission = $permissions->firstWhere('name', $permissionName);
                if ($permission) {
                    $permission->setAttribute(
                        'display_label',
                        $this->resolvePermissionLabel($permission, $groupDefinition['permissions'][$permissionName] ?? null)
                    );

                    $result[$groupKey]['permissions'][] = $permission;
                }
            }
        }

        return $result;
    }

    private function resolvePermissionLabel(Permission $permission, ?string $configuredLabel = null): string
    {
        if (!empty($permission->label)) {
            return (string) $permission->label;
        }

        if (!empty($configuredLabel)) {
            return $configuredLabel;
        }

        return $this->humanizePermissionName((string) $permission->name);
    }

    private function humanizePermissionName(string $permissionName): string
    {
        $parts = explode('.', $permissionName);
        $action = (string) array_pop($parts);
        $resource = implode('.', $parts);

        $actions = [
            'read' => 'Voir',
            'view' => 'Voir',
            'create' => 'Créer',
            'store' => 'Créer',
            'update' => 'Modifier',
            'edit' => 'Modifier',
            'delete' => 'Supprimer',
            'destroy' => 'Supprimer',
            'manage' => 'Gérer',
            'export' => 'Exporter',
            'import' => 'Importer',
            'send' => 'Envoyer',
            'sync' => 'Synchroniser',
            'approve' => 'Approuver',
            'reject' => 'Refuser',
            'restore' => 'Restaurer',
            'download' => 'Télécharger',
            'upload' => 'Téléverser',
        ];

        $actionLabel = $actions[$action] ?? Str::of($action)->replace(['-', '_'], ' ')->ucfirst()->toString();
        $resourceLabel = Str::of($resource ?: $permissionName)->replace(['.', '-', '_'], ' ')->title()->toString();

        return trim($actionLabel . ' - ' . $resourceLabel);
    }

    public function ensurePermissionsExist(): void
    {
        $this->tenantRoleService->ensureTenantRoles((int) Auth::user()->tenant_id);
    }

    public function getStats(): array
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $roles = $this->getRolesForTenant($tenantId);
        $defaultRoleNames = array_keys(config('rbac.default_roles', []));

        return [
            'total_roles' => $roles->count(),
            'custom_roles' => $roles
                ->where('is_system', false)
                ->reject(fn ($role) => in_array($role->name, $defaultRoleNames, true))
                ->count(),
            'total_permissions' => Permission::query()->whereNull('tenant_id')->count(),
            'users_without_role' => User::query()
                ->whereHas('tenantMemberships', fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->whereNull('role_id'))
                ->count(),
            'roles_distribution' => $roles->mapWithKeys(function ($role) use ($tenantId) {
                $count = \App\Models\TenantUserMembership::query()
                    ->where('tenant_id', $tenantId)
                    ->where('role_id', (int) $role->id)
                    ->where('status', 'active')
                    ->count();

                return [($role->label ?? $role->name) => $count];
            })->toArray(),
        ];
    }
}
