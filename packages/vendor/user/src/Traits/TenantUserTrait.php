<?php

namespace Vendor\User\Traits;

/**
 * TenantUserTrait
 *
 * À ajouter sur App\Models\User pour activer les fonctionnalités
 * du package user (invitations, rôles tenant, statuts, etc.)
 *
 * Usage :
 *   use Vendor\User\Traits\TenantUserTrait;
 *   class User extends Authenticatable {
 *       use TenantUserTrait;
 *   }
 */
trait TenantUserTrait
{
    // ── Accessors ──────────────────────────────────────────────────────────

    public function getRoleLabelAttribute(): string
    {
        return config("user.tenant_roles.{$this->role_in_tenant}", $this->role_in_tenant ?? '—');
    }

    public function getStatusLabelAttribute(): string
    {
        return config("user.user_statuses.{$this->status}", $this->status ?? '—');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active'   => 'success',
            'inactive' => 'danger',
            'invited'  => 'info',
            'suspended'=> 'secondary',
            default    => 'secondary',
        };
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->name ?? 'U', 0, 2));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) return null;
        return asset('storage/' . $this->avatar);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsInvitedAttribute(): bool
    {
        return $this->status === 'invited';
    }

    public function getIsSuspendedAttribute(): bool
    {
        return $this->status === 'suspended';
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function invitations()
    {
        return $this->hasMany(\Vendor\User\Models\UserInvitation::class, 'invited_by');
    }

    public function invitedByUser()
    {
        return $this->belongsTo(static::class, 'invited_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role_in_tenant', $role);
    }

    public function scopeOwners($query)
    {
        return $query->where('is_tenant_owner', true);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function hasRoleInTenant(string $role): bool
    {
        return $this->role_in_tenant === $role;
    }

    public function isOwnerOf(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId && $this->is_tenant_owner;
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role_in_tenant, ['owner', 'admin']);
    }

    public function canInviteRole(string $role): bool
    {
        // Un owner peut tout inviter, un admin ne peut pas inviter d'owner
        if ($this->is_tenant_owner) return true;
        if ($this->role_in_tenant === 'admin') return $role !== 'owner';
        return false;
    }
}