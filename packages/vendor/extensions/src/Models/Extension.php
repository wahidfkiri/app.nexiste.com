<?php

namespace Vendor\Extensions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vendor\CrmCore\Models\Tenant;

class Extension extends Model
{
    use SoftDeletes;

    protected $table = 'extensions';

    protected $fillable = [
        'slug', 'name', 'tagline', 'description', 'long_description', 'version',
        'category', 'icon', 'icon_bg_color', 'banner',
        'developer_name', 'developer_url', 'documentation_url', 'support_url',
        'pricing_type', 'price', 'currency', 'billing_cycle', 'yearly_price',
        'has_trial', 'trial_days',
        'status', 'is_featured', 'is_new', 'is_verified', 'is_official', 'sort_order',
        'compatible_modules', 'required_permissions', 'settings_schema', 'screenshots',
        'installs_count', 'active_installs_count', 'rating', 'ratings_count',
        'webhook_url', 'webhook_secret', 'meta',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'yearly_price'        => 'decimal:2',
        'rating'              => 'decimal:1',
        'has_trial'           => 'boolean',
        'is_featured'         => 'boolean',
        'is_new'              => 'boolean',
        'is_verified'         => 'boolean',
        'is_official'         => 'boolean',
        'compatible_modules'  => 'array',
        'required_permissions'=> 'array',
        'settings_schema'     => 'array',
        'screenshots'         => 'array',
        'meta'                => 'array',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function tenantExtensions(): HasMany
    {
        return $this->hasMany(TenantExtension::class);
    }

    public function activeTenants(): HasMany
    {
        return $this->hasMany(TenantExtension::class)->where('status', 'active');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ExtensionReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(ExtensionReview::class)->where('is_approved', true);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ExtensionActivityLog::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('status', 'active');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeFree($query)
    {
        return $query->where('pricing_type', 'free');
    }

    public function scopePaid($query)
    {
        return $query->whereIn('pricing_type', ['paid', 'freemium', 'per_seat', 'usage']);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('tagline', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('developer_name', 'like', "%{$term}%");
        });
    }

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $query->byCategory($filters['category']);
        }
        if (!empty($filters['pricing_type'])) {
            if ($filters['pricing_type'] === 'free') $query->free();
            if ($filters['pricing_type'] === 'paid') $query->paid();
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (!empty($filters['is_featured'])) {
            $query->where('is_featured', true);
        }

        $sort = $filters['sort'] ?? 'sort_order';
        $dir  = $filters['dir']  ?? 'asc';
        $allowed = ['name','price','installs_count','rating','created_at','sort_order'];
        $query->orderBy(in_array($sort, $allowed) ? $sort : 'sort_order', $dir);

        return $query;
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getCategoryLabelAttribute(): string
    {
        return config("extensions.categories.{$this->category}.label", $this->category);
    }

    public function getCategoryIconAttribute(): string
    {
        return config("extensions.categories.{$this->category}.icon", 'fa-puzzle-piece');
    }

    public function getCategoryColorAttribute(): string
    {
        return config("extensions.categories.{$this->category}.color", '#64748b');
    }

    public function getStatusLabelAttribute(): string
    {
        return config("extensions.extension_statuses.{$this->status}", $this->status);
    }

    public function getPricingLabelAttribute(): string
    {
        if ($this->pricing_type === 'free') return 'Gratuit';
        if ($this->pricing_type === 'paid') {
            $cycle = config("extensions.billing_cycles.{$this->billing_cycle}", '');
            return number_format($this->price, 2) . ' ' . $this->currency . ($cycle ? " / {$cycle}" : '');
        }
        return config("extensions.pricing_types.{$this->pricing_type}", $this->pricing_type);
    }

    public function getIsFreeAttribute(): bool
    {
        return $this->pricing_type === 'free';
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->resolveMediaUrl($this->icon, true);
    }

    public function getIconClassAttribute(): string
    {
        $icon = trim((string) ($this->icon ?? ''));
        if ($icon === '' || !$this->isFontAwesomeIcon($icon)) {
            return 'fas fa-puzzle-piece';
        }

        if (preg_match('/^fa-[a-z0-9-]+$/i', $icon) === 1) {
            return 'fas ' . $icon;
        }

        return $icon;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->resolveMediaUrl($this->banner);
    }

    // ── Methods ────────────────────────────────────────────────────────────

    /**
     * Vérifier si un tenant a cette extension activée
     */
    public function isActivatedFor(int $tenantId): bool
    {
        return $this->tenantExtensions()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['active', 'trial'])
            ->exists();
    }

    /**
     * Retourner l'activation d'un tenant spécifique
     */
    public function getActivationFor(int $tenantId): ?TenantExtension
    {
        return $this->tenantExtensions()
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Incrémenter le compteur d'installations
     */
    public function incrementInstalls(): void
    {
        $this->increment('installs_count');
        $this->increment('active_installs_count');
    }

    /**
     * Décrémenter le compteur d'installations actives
     */
    public function decrementActiveInstalls(): void
    {
        if ($this->active_installs_count > 0) {
            $this->decrement('active_installs_count');
        }
    }

    private function isFontAwesomeIcon(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (preg_match('/^fa-[a-z0-9-]+$/i', $value) === 1) {
            return true;
        }

        $hasFamily = preg_match('/(^|\s)(fa|fas|far|fal|fad|fab|fat|fa-solid|fa-regular|fa-light|fa-thin|fa-brands)(\s|$)/i', $value) === 1;
        $hasGlyph = preg_match('/(^|\s)fa-[a-z0-9-]+(\s|$)/i', $value) === 1;

        return $hasFamily && $hasGlyph;
    }

    private function resolveMediaUrl(?string $value, bool $ignoreFontAwesome = false): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if ($ignoreFontAwesome && $this->isFontAwesomeIcon($value)) {
            return null;
        }

        if (preg_match('/^(data:|https?:\/\/|\/\/)/i', $value) === 1) {
            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return asset(ltrim($value, '/'));
        }

        if (str_starts_with($value, 'storage/')) {
            return asset($value);
        }

        if (str_starts_with($value, '/')) {
            return asset(ltrim($value, '/'));
        }

        return asset('storage/' . ltrim($value, '/'));
    }
}
