<?php

namespace NexusExtensions\Slack\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class SlackToken extends Model
{
    protected $table = 'slack_tokens';

    protected $fillable = [
        'tenant_id',
        'connected_by',
        'bot_token',
        'bot_user_id',
        'app_id',
        'team_id',
        'team_name',
        'authed_user_id',
        'scope',
        'selected_channel_id',
        'selected_channel_name',
        'is_active',
        'connected_at',
        'disconnected_at',
        'last_sync_at',
    ];

    protected $casts = [
        'bot_token' => 'encrypted',
        'is_active' => 'boolean',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = ['bot_token'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

