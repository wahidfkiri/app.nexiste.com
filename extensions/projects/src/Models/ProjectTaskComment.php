<?php

namespace NexusExtensions\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class ProjectTaskComment extends Model
{
    use MultiTenantTrait;

    protected $table = 'project_task_comments';

    protected $fillable = [
        'tenant_id',
        'project_task_id',
        'user_id',
        'comment',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
