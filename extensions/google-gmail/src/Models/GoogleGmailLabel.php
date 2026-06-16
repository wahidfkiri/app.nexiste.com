<?php

namespace NexusExtensions\GoogleGmail\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleGmailLabel extends Model
{
    protected $table = 'google_gmail_labels';

    protected $fillable = [
        'tenant_id',
        'label_id',
        'name',
        'type',
        'messages_total',
        'messages_unread',
        'threads_total',
        'threads_unread',
        'color_background',
        'color_text',
        'is_visible',
    ];

    protected $casts = [
        'messages_total' => 'integer',
        'messages_unread' => 'integer',
        'threads_total' => 'integer',
        'threads_unread' => 'integer',
        'is_visible' => 'boolean',
    ];

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
