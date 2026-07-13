<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Traits\HasPublicUuid;

class SubscriptionPlan extends Model
{
    use SoftDeletes, HasPublicUuid;

    protected $fillable = [
        'name', 'slug', 'description', 'is_free', 'trial_days',
        'monthly_price', 'currency', 'features', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_active' => 'boolean',
        'trial_days' => 'integer',
        'sort_order' => 'integer',
        'monthly_price' => 'decimal:2',
        'features' => 'array',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(SubscriptionPlanPrice::class, 'plan_id')->orderBy('period_months');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calcul automatique du prix d'une période : mensuel × mois × (1 − remise%).
     */
    public static function computePeriodPrice(float $monthlyPrice, int $months, float $discountPercent = 0.0): float
    {
        $gross = $monthlyPrice * max(1, $months);
        $net = $gross * (1 - (max(0.0, min(100.0, $discountPercent)) / 100));

        return round($net, 2);
    }
}
