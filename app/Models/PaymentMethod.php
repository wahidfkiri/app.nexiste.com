<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\HasPublicUuid;

class PaymentMethod extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'name', 'provider', 'is_active', 'is_default', 'config', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'config' => 'array',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Garantit qu'un seul moyen de paiement est marqué par défaut.
     */
    public function markAsDefault(): void
    {
        static::query()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->forceFill(['is_default' => true, 'is_active' => true])->save();
    }
}
