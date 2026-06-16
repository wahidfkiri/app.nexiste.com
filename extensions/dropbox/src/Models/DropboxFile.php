<?php

namespace NexusExtensions\Dropbox\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Models\Tenant;

class DropboxFile extends Model
{
    use SoftDeletes;

    protected $table = 'dropbox_files';

    protected $fillable = [
        'tenant_id',
        'dropbox_id',
        'parent_path_lower',
        'path_lower',
        'path_display',
        'rev',
        'name',
        'mime_type',
        'is_folder',
        'size_bytes',
        'web_view_link',
        'download_link',
        'thumbnail_link',
        'shared_link',
        'is_shared',
        'created_by',
        'modified_by',
        'client_modified_at',
        'server_modified_at',
    ];

    protected $casts = [
        'is_folder' => 'boolean',
        'size_bytes' => 'integer',
        'is_shared' => 'boolean',
        'client_modified_at' => 'datetime',
        'server_modified_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function getSizeFormattedAttribute(): string
    {
        if ($this->is_folder) {
            return '-';
        }

        $bytes = (int) $this->size_bytes;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeFolders($query)
    {
        return $query->where('is_folder', true);
    }

    public function scopeFiles($query)
    {
        return $query->where('is_folder', false);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', '%' . $term . '%');
    }
}
