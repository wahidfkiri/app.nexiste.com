<?php

namespace Vendor\Extensions\Traits;

use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

/**
 * HasExtensions Trait
 *
 * À ajouter sur Vendor\CrmCore\Models\Tenant :
 *   use Vendor\Extensions\Traits\HasExtensions;
 *   class Tenant extends Model { use HasExtensions; }
 */
trait HasExtensions
{
    // ── Relations ──────────────────────────────────────────────────────────

    public function extensions()
    {
        return $this->hasMany(TenantExtension::class, 'tenant_id');
    }

    public function activeExtensions()
    {
        return $this->hasMany(TenantExtension::class, 'tenant_id')
            ->whereIn('status', ['active', 'trial'])
            ->with('extension');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Vérifie si le tenant a une extension activée (par slug)
     */
    public function hasExtension(string $slug): bool
    {
        return $this->extensions()
            ->whereHas('extension', fn($q) => $q->where('slug', $slug))
            ->whereIn('status', ['active', 'trial'])
            ->exists();
    }

    /**
     * Retourne l'activation d'une extension (par slug)
     */
    public function getExtension(string $slug): ?TenantExtension
    {
        return $this->extensions()
            ->whereHas('extension', fn($q) => $q->where('slug', $slug))
            ->with('extension')
            ->first();
    }

    /**
     * Retourne les paramètres d'une extension pour ce tenant
     */
    public function extensionSettings(string $slug): array
    {
        $activation = $this->getExtension($slug);
        return $activation?->settings ?? [];
    }

    /**
     * Compte les extensions actives
     */
    public function getActiveExtensionsCountAttribute(): int
    {
        return $this->extensions()->whereIn('status', ['active', 'trial'])->count();
    }
}