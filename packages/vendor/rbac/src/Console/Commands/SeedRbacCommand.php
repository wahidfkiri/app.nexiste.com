<?php

namespace Vendor\Rbac\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Vendor\CrmCore\Models\Tenant;
use Vendor\Rbac\Services\TenantRoleService;

class SeedRbacCommand extends Command
{
    protected $signature = 'rbac:seed
        {--tenant= : ID du tenant (tous si omis)}
        {--guard=web : Guard Spatie}';

    protected $description = 'Crée les permissions globales et les rôles par tenant.';

    public function __construct(protected TenantRoleService $tenantRoleService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantOption = $this->option('tenant');
        $tenants = $tenantOption
            ? Tenant::query()->where('id', (int) $tenantOption)->get()
            : Tenant::query()->get();

        $this->info('Initialisation RBAC multi-tenant...');

        foreach (['super_admin', 'super-admin'] as $roleName) {
            $superAdmin = Role::query()->firstOrCreate(
                [
                    'tenant_id' => null,
                    'name' => $roleName,
                    'guard_name' => 'web',
                ],
                [
                    'label' => 'Super administrateur',
                    'description' => 'Accès global plateforme',
                    'color' => '#dc2626',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $this->line("  ✓ Rôle global {$superAdmin->name}");
        }

        foreach ($tenants as $tenant) {
            $roles = $this->tenantRoleService->ensureTenantRoles((int) $tenant->id);
            $this->line("  ✓ Tenant #{$tenant->id} {$tenant->name} : {$roles->count()} rôle(s) prêts");
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->newLine();
        $this->info('RBAC multi-tenant initialisé avec succès.');
        $this->table(
            ['Élément', 'Total'],
            [
                ['Tenants traités', $tenants->count()],
                ['Rôles par défaut par tenant', count(config('rbac.default_roles', []))],
            ]
        );

        return self::SUCCESS;
    }
}
