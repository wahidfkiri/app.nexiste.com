<?php

namespace Vendor\User\Console\Commands;

use Illuminate\Console\Command;
use Vendor\CrmCore\Models\Tenant;
use Vendor\Rbac\Services\TenantRoleService;

class SeedTenantRoles extends Command
{
    protected $signature = 'user:seed-roles {--tenant= : ID du tenant}';

    protected $description = 'Crée les rôles par défaut pour les tenants.';

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

        foreach ($tenants as $tenant) {
            $roles = $this->tenantRoleService->ensureTenantRoles((int) $tenant->id);
            $this->line("Tenant #{$tenant->id} {$tenant->name} : {$roles->count()} rôle(s) synchronisés.");
        }

        $this->info('Rôles tenant synchronisés avec succès.');

        return self::SUCCESS;
    }
}
