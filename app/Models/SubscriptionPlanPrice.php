<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPlanPrice extends Model
{
    protected $fillable = [
        'plan_id', 'period_months', 'price', 'discount_percent', 'is_active',
    ];

    protected $casts = [
        'period_months' => 'integer',
        'price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Prix mensuel effectif de cette période (pour comparaison / affichage).
     */
    public function getMonthlyEquivalentAttribute(): float
    {
        $months = max(1, (int) $this->period_months);

        return round(((float) $this->price) / $months, 2);
    }
}
