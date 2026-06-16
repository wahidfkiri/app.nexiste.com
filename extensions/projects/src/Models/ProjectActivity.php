<?php

namespace NexusExtensions\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class ProjectActivity extends Model
{
    use MultiTenantTrait;

    protected $table = 'project_activities';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'project_task_id',
        'user_id',
        'event',
        'description',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
