<?php

namespace Vendor\CrmCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'logo',
        'timezone',
        'locale',
        'currency',
        'status',
        'trial_ends_at',
        'subscription_ends_at',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    // Relations
    public function users()
    {
        return $this->hasMany(config('auth.providers.users.model'), 'tenant_id');
    }

    public function clients()
    {
        return $this->hasMany('Vendor\Client\Models\Client', 'tenant_id');
    }

    public function settings()
    {
        return $this->hasMany(TenantSetting::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    // Accesseurs
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsTrialAttribute(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function getIsSubscribedAttribute(): bool
    {
        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }

    // Méthodes
    public static function generateSlug($name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;
        
        while (self::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $count++;
        }
        
        return $slug;
    }

    public function getSetting($key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public function setSetting($key, $value)
    {
        return $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}