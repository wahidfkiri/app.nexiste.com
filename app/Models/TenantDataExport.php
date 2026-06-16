<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDataExport extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'provider',
        'status',
        'progress_percent',
        'total_steps',
        'current_step_index',
        'current_step_key',
        'current_step_label',
        'file_name',
        'workspace_path',
        'local_zip_path',
        'remote_file_id',
        'remote_url',
        'error_message',
        'meta',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'progress_percent' => 'integer',
        'total_steps' => 'integer',
        'current_step_index' => 'integer',
        'meta' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'running']);
    }
}
