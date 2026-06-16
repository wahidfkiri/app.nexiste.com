<?php

namespace Vendor\Invoice\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class Currency extends Model
{
    use MultiTenantTrait;

    protected $table = 'currencies';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'symbol',
        'symbol_position',
        'decimals',
        'thousands_sep',
        'decimal_sep',
        'exchange_rate',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
