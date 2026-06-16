<?php

namespace NexusExtensions\NotionWorkspace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class NotionWorkspaceToken extends Model
{
    protected $table = 'notion_workspace_tokens';

    protected $fillable = [
        'tenant_id',
        'connected_by',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'notion_workspace_id',
        'notion_workspace_name',
        'notion_workspace_icon',
        'notion_bot_id',
        'notion_owner_type',
        'notion_user_id',
        'notion_user_name',
        'notion_user_email',
        'notion_user_avatar_url',
        'is_active',
        'connected_at',
        'disconnected_at',
        'last_synced_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        $buffer = (int) config('notion-workspace.token.refresh_buffer', 300);

        return $this->token_expires_at->copy()->subSeconds($buffer)->isPast();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }
}
