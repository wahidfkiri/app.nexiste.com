<?php

namespace NexusExtensions\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class ProjectTaskChecklist extends Model
{
    use MultiTenantTrait;

    protected $table = 'project_task_checklists';

    protected $fillable = [
        'tenant_id',
        'project_task_id',
        'title',
        'is_done',
        'position',
        'done_by',
        'done_at',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'done_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function doneBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'done_by');
    }
}
