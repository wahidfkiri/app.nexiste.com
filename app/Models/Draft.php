<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Models\Tenant;

class Draft extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'type',
        'data',
        'route',
        'expires_at',
        'reminded_at',
    ];

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForActor(Builder $query, int $userId, int $tenantId): Builder
    {
        return $query
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }
}
