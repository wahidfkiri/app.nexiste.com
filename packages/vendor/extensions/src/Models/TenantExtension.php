<?php

namespace Vendor\Extensions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Vendor\CrmCore\Models\Tenant;

class TenantExtension extends Model
{
    protected $table = 'tenant_extensions';

    protected $fillable = [
        'tenant_id', 'extension_id', 'activated_by',
        'status', 'activated_at', 'deactivated_at', 'suspended_at',
        'trial_ends_at', 'subscription_ends_at',
        'settings', 'credentials', 'api_key',
        'billing_cycle', 'price_paid', 'currency', 'payment_reference',
        'suspension_reason', 'suspended_by',
        'api_calls_count', 'last_used_at', 'internal_notes',
    ];

    protected $casts = [
        'activated_at'         => 'datetime',
        'deactivated_at'       => 'datetime',
        'suspended_at'         => 'datetime',
        'trial_ends_at'        => 'datetime',
        'subscription_ends_at' => 'datetime',
        'last_used_at'         => 'datetime',
        'settings'             => 'array',
        'credentials'          => 'encrypted:array',    // Chiffré en DB
        'price_paid'           => 'decimal:2',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function activatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return config("extensions.activation_statuses.{$this->status}", $this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active'    => 'success',
            'trial'     => 'info',
            'pending'   => 'warning',
            'suspended' => 'danger',
            'expired'   => 'danger',
            'inactive'  => 'secondary',
            default     => 'secondary',
        };
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['active', 'trial']);
    }

    public function getIsTrialAttribute(): bool
    {
        return $this->status === 'trial';
    }

    public function getIsExpiredAttribute(): bool
    {
        if ($this->status === 'trial' && $this->trial_ends_at?->isPast()) return true;
        if ($this->subscription_ends_at && $this->subscription_ends_at->isPast()) return true;
        return false;
    }

    public function getTrialDaysRemainingAttribute(): int
    {
        if (!$this->trial_ends_at || $this->status !== 'trial') return 0;
        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'trial']);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeExpiredTrials($query)
    {
        return $query->where('status', 'trial')
            ->where('trial_ends_at', '<', now());
    }
}
