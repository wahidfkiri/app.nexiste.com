<?php

namespace Vendor\CrmCore\Traits;

use Vendor\CrmCore\Models\Tenant;

trait MultiTenantTrait
{
    public static function bootMultiTenantTrait(): void
    {
        // Auto-assign tenant_id on create
        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id && auth()->user()->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        // Global scope: always filter by current tenant
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where(
                    (new static)->getTable() . '.tenant_id',
                    auth()->user()->tenant_id
                );
            }
        });
    }

    /**
     * Scope to filter by a specific tenant (bypass global scope)
     */
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->withoutGlobalScope('tenant')
                     ->where($this->getTable() . '.tenant_id', $tenantId);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
