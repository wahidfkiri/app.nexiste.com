<?php

namespace NexusExtensions\GoogleMeet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class GoogleMeetToken extends Model
{
    protected $table = 'google_meet_tokens';

    protected $fillable = [
        'tenant_id',
        'connected_by',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'google_account_id',
        'google_email',
        'google_name',
        'google_avatar_url',
        'selected_calendar_id',
        'selected_calendar_summary',
        'is_active',
        'connected_at',
        'disconnected_at',
        'last_sync_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

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

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        $buffer = (int) config('google-meet.token.refresh_buffer', 300);

        return $this->token_expires_at->copy()->subSeconds($buffer)->isPast();
    }

    public function toGoogleToken(): array
    {
        return array_filter([
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_in' => $this->token_expires_at
                ? max(0, now()->diffInSeconds($this->token_expires_at, false))
                : 3600,
            'token_type' => 'Bearer',
            'created' => $this->token_expires_at
                ? $this->token_expires_at->copy()->subHour()->timestamp
                : now()->timestamp,
        ]);
    }
}
