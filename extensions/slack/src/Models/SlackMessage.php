<?php

namespace NexusExtensions\Slack\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class SlackMessage extends Model
{
    protected $table = 'slack_messages';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'slack_ts',
        'thread_ts',
        'user_id',
        'username',
        'text',
        'blocks',
        'attachments',
        'reactions',
        'is_bot',
        'is_deleted',
        'sent_at',
        'edited_at',
        'raw',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'blocks' => 'array',
        'attachments' => 'array',
        'reactions' => 'array',
        'is_bot' => 'boolean',
        'is_deleted' => 'boolean',
        'sent_at' => 'datetime',
        'edited_at' => 'datetime',
        'raw' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}

