<?php

namespace Vendor\Rbac\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Vendor\Rbac\Repositories\RbacRepository;

class RbacService
{
    public function __construct(protected RbacRepository $repository)
    {
    }

    public function getFilteredRoles(array $filters)
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $this->repository->getFilteredRoles($filters, $perPage);
    }

    public function getRolesForCurrentTenant()
    {
        return $this->repository->getRolesForTenant(Auth::user()->tenant_id);
    }

    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $data['name'] = $this->generateRoleSlug($data['label']);

            $role = $this->repository->createRole($data);

            if (!empty($data['permissions'])) {
                $this->repository->syncRolePermissions($role, $data['permissions']);
            }

            $this->clearCache();

            Log::channel('daily')->info("[RBAC] Role cree : {$role->name}", [
                'tenant_id' => Auth::user()->tenant_id,
                'permissions' => $data['permissions'] ?? [],
            ]);

            return $role->fresh(['permissions']);
        });
    }

    public function updateRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $this->assertNotSystem($role);

            $role = $this->repository->updateRole($role, $data);

            if (array_key_exists('permissions', $data)) {
                $this->repository->syncRolePermissions($role, $data['permissions'] ?? []);
            }

            $this->clearCache();

            Log::channel('daily')->info("[RBAC] Role modifie : {$role->name}");

            return $role->fresh(['permissions']);
        });
    }

    public function deleteRole(Role $role): bool
    {
        $this->assertDeletableRole($role);

        return DB::transaction(function () use ($role) {
            $result = $this->repository->deleteRole($role);
            $this->clearCache();
            Log::channel('daily')->info("[RBAC] Role supprime : {$role->name}");

            return $result;
        });
    }

    public function syncPermissions(Role $role, array $permissionNames): Role
    {
        $this->assertNotSystem($role);

        return DB::transaction(function () use ($role, $permissionNames) {
            $result = $this->repository->syncRolePermissions($role, $permissionNames);
            $this->clearCache();
            Log::channel('daily')->info("[RBAC] Permissions synchronisees pour le role {$role->name}", [
                'permissions' => $permissionNames,
            ]);

            return $result;
        });
    }

    public function getPermissionsGrouped(): array
    {
        return $this->repository->getPermissionsGrouped();
    }

    public function getAllPermissions()
    {
        return $this->repository->getAllPermissions();
    }

    public function ensurePermissionsExist(): void
    {
        $this->repository->ensurePermissionsExist();
        $this->clearCache();
    }

    public function getStats(): array
    {
        return $this->repository->getStats();
    }

    private function generateRoleSlug(string $label): string
    {
        $tenantId = Auth::user()->tenant_id;
        $base = Str::slug($label, '_');
        $slug = $base;
        $count = 1;

        while (Role::where('name', $slug)->where('tenant_id', $tenantId)->exists()) {
            $slug = $base . '_' . $count++;
        }

        return $slug;
    }

    private function assertNotSystem(Role $role): void
    {
        if ($role->is_system || in_array($role->name, config('rbac.system_roles', []), true)) {
            throw new \RuntimeException(__('rbac::rbac.errors.system_role_locked'));
        }
    }

    private function assertDeletableRole(Role $role): void
    {
        if ($role->is_system || in_array($role->name, config('rbac.system_roles', []), true)) {
            throw new \RuntimeException(__('rbac::rbac.errors.system_role_delete_forbidden'));
        }

        if (array_key_exists((string) $role->name, config('rbac.default_roles', []))) {
            throw new \RuntimeException(__('rbac::rbac.errors.default_role_delete_forbidden'));
        }
    }

    private function clearCache(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
