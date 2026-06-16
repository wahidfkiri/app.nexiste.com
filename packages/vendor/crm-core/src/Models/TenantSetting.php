<?php

namespace Vendor\CrmCore\Models;

use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    protected $table = 'tenant_settings';

    protected $fillable = ['tenant_id', 'key', 'value'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
