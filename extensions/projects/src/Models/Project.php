<?php

namespace NexusExtensions\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class Project extends Model
{
    use SoftDeletes, MultiTenantTrait;

    protected $table = 'projects';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'owner_id',
        'name',
        'slug',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'budget',
        'progress',
        'color',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'budget' => 'decimal:2',
        'progress' => 'integer',
        'metadata' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function client()
    {
        return $this->belongsTo(\Vendor\Client\Models\Client::class, 'client_id');
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class, 'project_id');
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'project_id');
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class, 'project_id')->latest();
    }

    public function activities()
    {
        return $this->hasMany(ProjectActivity::class, 'project_id');
    }

    public function recalculateProgress(): void
    {
        $total = $this->tasks()->count();
        if ($total <= 0) {
            $this->update(['progress' => 0]);
            return;
        }

        $done = $this->tasks()->where('status', 'done')->count();
        $progress = (int) round(($done / $total) * 100);

        $data = ['progress' => max(0, min(100, $progress))];
        if ($data['progress'] >= 100 && $this->status !== 'completed') {
            $data['status'] = 'completed';
            $data['completed_at'] = now();
        }

        $this->update($data);
    }
}
