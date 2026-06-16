<?php

namespace Vendor\Automation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Vendor\CrmCore\Models\Tenant;

class AutomationSuggestion extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected $table = 'automation_suggestions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'source_event',
        'source_type',
        'source_id',
        'type',
        'label',
        'confidence',
        'payload',
        'meta',
        'status',
        'dedupe_key',
        'pending_dedupe_key',
        'expires_at',
        'accepted_at',
        'accepted_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts = [
        'confidence' => 'float',
        'payload' => 'array',
        'meta' => 'array',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function automationEvents(): HasMany
    {
        return $this->hasMany(AutomationEvent::class, 'triggered_by_suggestion_id');
    }

    public function latestFailedEvent(): HasOne
    {
        return $this->hasOne(AutomationEvent::class, 'triggered_by_suggestion_id')
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('status', AutomationEvent::STATUS_FAILED);
            });
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class, 'automation_suggestion_id');
    }

    public function isActionable(): bool
    {
        return $this->status === self::STATUS_PENDING
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function markExpiredIfNeeded(): void
    {
        if ($this->status === self::STATUS_PENDING && $this->expires_at && $this->expires_at->isPast()) {
            $this->forceFill([
                'status' => self::STATUS_EXPIRED,
                'pending_dedupe_key' => null,
            ])->save();
        }
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
