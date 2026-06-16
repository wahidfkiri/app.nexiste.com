<?php

namespace NexusExtensions\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class ProjectFile extends Model
{
    use MultiTenantTrait;

    protected $table = 'project_files';

    protected $fillable = [
        'tenant_id',
        'project_id',
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

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }
}

