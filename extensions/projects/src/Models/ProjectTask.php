<?php

namespace NexusExtensions\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class ProjectTask extends Model
{
    use SoftDeletes, MultiTenantTrait;

    protected $table = 'project_tasks';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'parent_task_id',
        'client_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'position',
        'start_date',
        'due_date',
        'completed_at',
        'estimate_hours',
        'spent_hours',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'estimate_hours' => 'decimal:2',
        'spent_hours' => 'decimal:2',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function client()
    {
        return $this->belongsTo(\Vendor\Client\Models\Client::class, 'client_id');
    }

    public function comments()
    {
        return $this->hasMany(ProjectTaskComment::class, 'project_task_id');
    }

    public function checklist()
    {
        return $this->hasMany(ProjectTaskChecklist::class, 'project_task_id')->orderBy('position');
    }
}
