<?php

namespace Vendor\Invoice\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class TaxRate extends Model
{
    use MultiTenantTrait;

    protected $table = 'tax_rates';

    protected $fillable = [
        'tenant_id',
        'name',
        'rate',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
