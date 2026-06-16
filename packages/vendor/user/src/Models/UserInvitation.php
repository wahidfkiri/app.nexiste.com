<?php

namespace Vendor\User\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Vendor\CrmCore\Models\Tenant;

class UserInvitation extends Model
{
    use Notifiable;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    protected $table = 'user_invitations';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'invited_by',
        'email',
        'role_id',
        'role_in_tenant',
        'token',
        'expires_at',
        'status',
        'pending_email_key',
        'accepted_at',
        'revoked_at',
        'revoked_reason',
        'resend_count',
        'last_resent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_resent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(config('permission.models.role'), 'role_id');
    }

    public function routeNotificationForMail(): string
    {
        return (string) $this->email;
    }

    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return $this->accepted_at === null && $this->revoked_at === null;
    }

    public function markExpiredIfNeeded(): void
    {
        if (
            $this->status === self::STATUS_PENDING
            && $this->expires_at
            && $this->expires_at->isPast()
        ) {
            $this->forceFill([
                'status' => self::STATUS_EXPIRED,
                'pending_email_key' => null,
            ])->save();
        }
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expires_at && $this->expires_at->isPast() && $this->accepted_at === null);
    }

    public function getIsAcceptedAttribute(): bool
    {
        return $this->status === self::STATUS_ACCEPTED || $this->accepted_at !== null;
    }

    public function getIsRevokedAttribute(): bool
    {
        return $this->status === self::STATUS_REVOKED || $this->revoked_at !== null;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->isUsable();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'Acceptée',
            self::STATUS_REVOKED => 'Révoquée',
            self::STATUS_EXPIRED => 'Expirée',
            default => 'En attente',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'success',
            self::STATUS_REVOKED => 'danger',
            self::STATUS_EXPIRED => 'warning',
            default => 'info',
        };
    }

    public function scopeActive($query)
    {
        return $query
            ->where('status', self::STATUS_PENDING)
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
