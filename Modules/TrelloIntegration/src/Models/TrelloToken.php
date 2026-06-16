<?php

namespace Modules\TrelloIntegration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class TrelloToken extends Model
{
    use MultiTenantTrait;

    protected $table = 'trello_tokens';

    protected $fillable = [
        'tenant_id',
        'connected_by',
        'api_token',
        'scopes',
        'token_expiration',
        'token_expires_at',
        'trello_member_id',
        'trello_username',
        'trello_full_name',
        'trello_avatar_url',
        'trello_profile_url',
        'is_active',
        'connected_at',
        'disconnected_at',
        'last_synced_at',
    ];

    protected $casts = [
        'api_token' => 'encrypted',
        'scopes' => 'array',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['api_token'];

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->token_expires_at !== null && $this->token_expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
