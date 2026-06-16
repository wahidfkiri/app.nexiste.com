<?php 


namespace NexusExtensions\GoogleDrive\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;
use App\Models\User;

class GoogleDriveFile extends Model
{
    use SoftDeletes;

    protected $table = 'google_drive_files';

    protected $fillable = [
        'tenant_id', 'drive_id', 'parent_drive_id',
        'name', 'mime_type', 'is_folder', 'size_bytes',
        'web_view_link', 'web_content_link', 'thumbnail_link', 'icon_link',
        'is_shared', 'shared_with',
        'created_by', 'modified_by',
        'drive_created_at', 'drive_modified_at',
    ];

    protected $casts = [
        'is_folder'         => 'boolean',
        'is_shared'         => 'boolean',
        'size_bytes'        => 'integer',
        'drive_created_at'  => 'datetime',
        'drive_modified_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function modifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'modified_by'); }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getSizeFormattedAttribute(): string
    {
        if ($this->is_folder) return '—';
        $bytes = $this->size_bytes;
        if ($bytes < 1024) return $bytes . ' o';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' Ko';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' Mo';
        return round($bytes / 1073741824, 2) . ' Go';
    }

    public function getMimeIconAttribute(): array
    {
        $icons = config('google-drive.mime_icons', []);
        if ($this->is_folder) return $icons['application/vnd.google-apps.folder'] ?? ['icon' => 'fa-folder', 'color' => '#f59e0b'];
        return $icons[$this->mime_type] ?? $icons['default'] ?? ['icon' => 'fa-file', 'color' => '#64748b'];
    }

    public function getExtensionAttribute(): string
    {
        if ($this->is_folder) return '';
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId) { return $query->where('tenant_id', $tenantId); }
    public function scopeFolders($query) { return $query->where('is_folder', true); }
    public function scopeFiles($query) { return $query->where('is_folder', false); }
    public function scopeInFolder($query, ?string $parentId) { return $query->where('parent_drive_id', $parentId); }
    public function scopeSearch($query, string $term) {
        return $query->where('name', 'like', "%{$term}%");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
