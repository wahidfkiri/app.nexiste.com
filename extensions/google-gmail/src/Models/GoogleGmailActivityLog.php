<?php

namespace NexusExtensions\GoogleGmail\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleGmailActivityLog extends Model
{
    protected $table = 'google_gmail_activity_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'gmail_message_id',
        'thread_id',
        'action',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
