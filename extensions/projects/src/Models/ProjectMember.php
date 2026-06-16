<?php

namespace NexusExtensions\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class ProjectMember extends Model
{
    use MultiTenantTrait;

    protected $table = 'project_members';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'user_id',
        'role',
        'is_active',
        'invited_by',
        'joined_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function invitedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'invited_by');
    }
}
