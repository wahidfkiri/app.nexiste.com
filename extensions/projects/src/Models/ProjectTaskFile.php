<?php

namespace NexusExtensions\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class ProjectTaskFile extends Model
{
    use MultiTenantTrait;

    protected $table = 'project_task_files';

    protected $fillable = [
        'tenant_id',
        'project_task_id',
        'uploaded_by',
        'drive_file_id',
        'name',
        'mime_type',
        'size_bytes',
        'web_view_link',
        'download_link',
        'meta',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'meta' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id');
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }
}

