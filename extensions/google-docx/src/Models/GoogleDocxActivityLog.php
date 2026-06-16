<?php

namespace NexusExtensions\GoogleDocx\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class GoogleDocxActivityLog extends Model
{
    protected $table = 'google_docx_activity_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'document_id',
        'document_title',
        'action',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
