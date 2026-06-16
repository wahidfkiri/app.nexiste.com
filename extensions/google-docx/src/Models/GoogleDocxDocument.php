<?php

namespace NexusExtensions\GoogleDocx\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Models\Tenant;

class GoogleDocxDocument extends Model
{
    use SoftDeletes;

    protected $table = 'google_docx_documents';

    protected $fillable = [
        'tenant_id',
        'document_id',
        'title',
        'document_url',
        'mime_type',
        'is_shared',
        'revision_id',
        'content_chars',
        'created_by',
        'modified_by',
        'drive_created_at',
        'drive_modified_at',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
        'revision_id' => 'integer',
        'content_chars' => 'integer',
        'drive_created_at' => 'datetime',
        'drive_modified_at' => 'datetime',
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

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
