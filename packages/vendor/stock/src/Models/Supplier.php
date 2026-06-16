<?php

namespace Vendor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class Supplier extends Model
{
    use SoftDeletes, MultiTenantTrait;

    protected $table = 'stock_suppliers';

    protected $fillable = [
        'tenant_id', 'user_id', 'name', 'email', 'phone', 'contact_name',
        'address', 'city', 'country', 'notes',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }
}
