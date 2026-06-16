<?php

namespace NexusExtensions\GoogleGmail\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class GoogleGmailMessage extends Model
{
    protected $table = 'google_gmail_messages';

    protected $fillable = [
        'tenant_id',
        'gmail_message_id',
        'thread_id',
        'message_id_header',
        'subject',
        'sender',
        'to_recipients',
        'cc_recipients',
        'snippet',
        'body_text',
        'body_html',
        'label_ids',
        'has_attachments',
        'is_read',
        'is_starred',
        'sent_at',
        'gmail_internal_date',
        'web_url',
        'last_synced_at',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'to_recipients' => 'array',
        'cc_recipients' => 'array',
        'label_ids' => 'array',
        'has_attachments' => 'boolean',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'sent_at' => 'datetime',
        'gmail_internal_date' => 'datetime',
        'last_synced_at' => 'datetime',
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
