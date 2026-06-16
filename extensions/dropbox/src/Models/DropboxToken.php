<?php

namespace NexusExtensions\Dropbox\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class DropboxToken extends Model
{
    protected $table = 'dropbox_tokens';

    protected $fillable = [
        'tenant_id',
        'connected_by',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'dropbox_account_id',
        'dropbox_email',
        'dropbox_name',
        'dropbox_avatar_url',
        'dropbox_root_id',
        'dropbox_root_path',
        'space_quota_total_gb',
        'space_quota_used_gb',
        'is_active',
        'last_sync_at',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'is_active' => 'boolean',
        'space_quota_total_gb' => 'decimal:2',
        'space_quota_used_gb' => 'decimal:2',
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

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        $buffer = (int) config('dropbox.token.refresh_buffer', 300);

        return $this->token_expires_at->copy()->subSeconds($buffer)->isPast();
    }

    public function getQuotaPercentAttribute(): float
    {
        if (!$this->space_quota_total_gb || (float) $this->space_quota_total_gb <= 0) {
            return 0.0;
        }

        return round(((float) $this->space_quota_used_gb / (float) $this->space_quota_total_gb) * 100, 1);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
