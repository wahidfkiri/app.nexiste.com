<?php

namespace NexusExtensions\Slack\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class SlackChannel extends Model
{
    protected $table = 'slack_channels';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'name',
        'is_private',
        'is_im',
        'is_mpim',
        'is_archived',
        'is_member',
        'is_selected',
        'num_members',
        'topic',
        'purpose',
        'last_message_ts',
        'last_message_at',
        'synced_at',
        'raw',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_im' => 'boolean',
        'is_mpim' => 'boolean',
        'is_archived' => 'boolean',
        'is_member' => 'boolean',
        'is_selected' => 'boolean',
        'num_members' => 'integer',
        'last_message_at' => 'datetime',
        'synced_at' => 'datetime',
        'raw' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}

