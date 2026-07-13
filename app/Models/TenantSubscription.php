<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;
use Vendor\CrmCore\Traits\HasPublicUuid;

class TenantSubscription extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'tenant_id', 'plan_id', 'plan_price_id', 'payment_method', 'status',
        'is_trial', 'amount', 'currency', 'starts_at', 'ends_at',
        'trial_ends_at', 'reminder_sent_at', 'meta',
    ];

    protected $casts = [
        'is_trial' => 'boolean',
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlanPrice::class, 'plan_price_id');
    }

    /**
     * Abonnement actuellement valable (actif ou en essai, non expiré).
     */
    public function isCurrentlyActive(): bool
    {
        if (! in_array($this->status, ['active', 'trialing'], true)) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture();
    }

    public function scopeActiveForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId)
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }
}
