<?php

namespace NexusExtensions\Dropbox\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class DropboxActivityLog extends Model
{
    protected $table = 'dropbox_activity_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'dropbox_file_id',
        'file_name',
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
