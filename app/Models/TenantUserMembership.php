<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Models\Tenant;

class TenantUserMembership extends Model
{
    use HasFactory;

    protected $table = 'tenant_user_memberships';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'role_id',
        'role_in_tenant',
        'is_tenant_owner',
        'status',
        'invited_by',
        'joined_at',
    ];

    protected $casts = [
        'is_tenant_owner' => 'boolean',
        'joined_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function role()
    {
        return $this->belongsTo(config('permission.models.role'), 'role_id');
    }
}
