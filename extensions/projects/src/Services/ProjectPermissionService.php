<?php

namespace NexusExtensions\Projects\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProjectPermissionService
{
    public function ensurePermissions(): void
    {
        if (
            !Schema::hasTable('permissions')
            || !Schema::hasTable('roles')
            || !Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $permissions = config('projects.permissions', []);
        if (empty($permissions)) {
            return;
        }

        foreach ($permissions as $name) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['label' => $this->labelFromName($name), 'group' => 'projects', 'description' => 'Permission module projets']
            );

            if (Schema::hasColumn('permissions', 'group') && empty($permission->group)) {
                $permission->group = 'projects';
            }
            if (Schema::hasColumn('permissions', 'label') && empty($permission->label)) {
                $permission->label = $this->labelFromName($name);
            }
            if (Schema::hasColumn('permissions', 'description') && empty($permission->description)) {
                $permission->description = 'Permission module projets';
            }
            $permission->save();
        }

        $allPermissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        $ownerAdmin = Role::query()->whereIn('name', ['owner', 'admin'])->get();
        foreach ($ownerAdmin as $role) {
            foreach ($allPermissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => (int) $permissionId,
                    'role_id' => (int) $role->id,
                ]);
            }
        }

        $managerPerms = [
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.manage_tasks',
            'projects.comment',
        ];

        $managerRoles = Role::query()->where('name', 'manager')->get();
        $managerPermissionIds = Permission::query()
            ->whereIn('name', $managerPerms)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        foreach ($managerRoles as $role) {
            foreach ($managerPermissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => (int) $permissionId,
                    'role_id' => (int) $role->id,
                ]);
            }
        }
    }

    private function labelFromName(string $permission): string
    {
        return match ($permission) {
            'projects.view' => 'Voir projets',
            'projects.create' => 'Creer projets',
            'projects.update' => 'Modifier projets',
            'projects.delete' => 'Supprimer projets',
            'projects.manage_members' => 'Gerer membres projet',
            'projects.manage_tasks' => 'Gerer taches projet',
            'projects.comment' => 'Commenter taches projet',
            'projects.admin' => 'Administration projets',
            default => $permission,
        };
    }
}
