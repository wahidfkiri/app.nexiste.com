<?php

namespace NexusExtensions\NotionWorkspace\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NotionPermissionService
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

        $permissions = config('notion-workspace.permissions', []);
        if (empty($permissions)) {
            return;
        }

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'label' => $this->labelFromName($permissionName),
                    'group' => 'notion',
                    'description' => 'Permission espace notion',
                ]
            );

            if (Schema::hasColumn('permissions', 'group') && empty($permission->group)) {
                $permission->group = 'notion';
            }
            if (Schema::hasColumn('permissions', 'label') && empty($permission->label)) {
                $permission->label = $this->labelFromName($permissionName);
            }
            if (Schema::hasColumn('permissions', 'description') && empty($permission->description)) {
                $permission->description = 'Permission espace notion';
            }
            $permission->save();
        }

        $allPermissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        $adminRoles = Role::query()->whereIn('name', ['owner', 'admin'])->get();
        foreach ($adminRoles as $role) {
            foreach ($allPermissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => (int) $permissionId,
                    'role_id' => (int) $role->id,
                ]);
            }
        }

        $managerPermissions = ['notion.view', 'notion.create', 'notion.update', 'notion.comment', 'notion.share'];
        $managerPermissionIds = Permission::query()
            ->whereIn('name', $managerPermissions)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();

        $managerRoles = Role::query()->where('name', 'manager')->get();
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
            'notion.view' => 'Voir pages',
            'notion.create' => 'Creer pages',
            'notion.update' => 'Modifier pages',
            'notion.delete' => 'Supprimer pages',
            'notion.share' => 'Partager pages',
            'notion.comment' => 'Commenter pages',
            'notion.admin' => 'Administration notion',
            default => $permission,
        };
    }
}
