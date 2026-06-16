<?php

namespace NexusExtensions\GoogleSheets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class GoogleSheetsToken extends Model
{
    protected $table = 'google_sheets_tokens';

    protected $fillable = [
        'tenant_id', 'connected_by',
        'access_token', 'refresh_token', 'token_expires_at',
        'google_account_id', 'google_email', 'google_name', 'google_avatar_url',
        'is_active', 'last_sync_at', 'connected_at', 'disconnected_at',
    ];

    protected $casts = [
        'access_token'     => 'encrypted',
        'refresh_token'    => 'encrypted',
        'token_expires_at' => 'datetime',
        'last_sync_at'     => 'datetime',
        'connected_at'     => 'datetime',
        'disconnected_at'  => 'datetime',
        'is_active'        => 'boolean',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    // ── Relations ──────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->token_expires_at) return false;
        $buffer = (int) config('google-sheets.token.refresh_buffer', 300);
        return $this->token_expires_at->copy()->subSeconds($buffer)->isPast();
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ── Methods ────────────────────────────────────────────────────────────

    public function toGoogleToken(): array
    {
        return array_filter([
            'access_token'  => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'expires_in'    => $this->token_expires_at
                                ? max(0, now()->diffInSeconds($this->token_expires_at, false))
                                : 3600,
            'token_type'    => 'Bearer',
            'created'       => $this->token_expires_at
                                ? $this->token_expires_at->copy()->subHour()->timestamp
                                : now()->timestamp,
        ]);
    }
}