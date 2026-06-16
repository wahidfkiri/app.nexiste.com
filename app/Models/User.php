<?php

namespace App\Models;

use App\Notifications\AccountActivationNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Vendor\CrmCore\Models\Tenant;
use Vendor\User\Traits\TenantUserTrait;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, TenantUserTrait;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'avatar',
        'phone',
        'position',
        'company',
        'bio',
        'is_active',
        'tenant_id',
        'role_in_tenant',
        'is_tenant_owner',
        'last_login_at',
        'last_login_ip',
        'status',
        'auth_provider',
        'auth_provider_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_tenant_owner' => 'boolean',
        'last_login_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONS ====================
    
    /**
     * Relation avec le tenant (entreprise)
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Clients créés par cet utilisateur
     */
    public function clients()
    {
        return $this->hasMany('Vendor\Client\Models\Client', 'user_id');
    }

    /**
     * Clients assignés à cet utilisateur
     */
    public function assignedClients()
    {
        return $this->hasMany('Vendor\Client\Models\Client', 'assigned_to');
    }

    public function tenantMemberships()
    {
        return $this->hasMany(TenantUserMembership::class, 'user_id');
    }

    // ==================== ACCESSORS ====================
    
    /**
     * Récupérer le nom complet
     */
    public function getFullNameAttribute(): string
    {        
        return $this->name ?? $this->email;
    }

    /**
     * Récupérer les initiales (2 premières lettres)
     */
    public function getInitialsAttribute(): string
    {
        
        
        // Méthode 2: À partir du nom complet
        if ($this->name) {
            $parts = explode(' ', $this->name);
            if (count($parts) >= 2) {
                return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
            }
            return strtoupper(substr($this->name, 0, 2));
        }
        
        // Méthode 3: À partir de l'email
        if ($this->email) {
            return strtoupper(substr($this->email, 0, 2));
        }
        
        return 'JD';
    }

    /**
     * Récupérer le rôle dans le tenant (libellé)
     */
    public function getRoleInTenantLabelAttribute(): string
    {
        $roles = [
            'owner' => 'Propriétaire',
            'admin' => 'Administrateur',
            'manager' => 'Gestionnaire',
            'user' => 'Utilisateur',
        ];
        
        return $roles[$this->role_in_tenant] ?? $this->role_in_tenant;
    }

    /**
     * Vérifier si l'utilisateur est le propriétaire du tenant
     */
    public function getIsOwnerOfTenantAttribute(): bool
    {
        return $this->is_tenant_owner === true || $this->role_in_tenant === 'owner';
    }

    // ==================== SCOPES ====================
    
    /**
     * Scope pour les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les utilisateurs inactifs
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope pour les utilisateurs d'un tenant spécifique
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where(function (Builder $q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);

            if (Schema::hasTable('tenant_user_memberships')) {
                $q->orWhereHas('tenantMemberships', function (Builder $membership) use ($tenantId) {
                    $membership
                        ->where('tenant_id', $tenantId)
                        ->where('status', 'active');
                });
            }
        });
    }

    /**
     * Scope pour les propriétaires de tenant
     */
    public function scopeTenantOwners($query)
    {
        return $query->where('is_tenant_owner', true);
    }

    /**
     * Scope pour les administrateurs
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role_in_tenant', ['owner', 'admin']);
    }

    // ==================== METHODS ====================
    
    /**
     * Vérifier si l'utilisateur peut gérer le tenant
     */
    public function canManageTenant(): bool
    {
        return $this->hasTenantRole(['owner', 'admin']);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification((string) $token));
    }

    /**
     * Vérifier si l'utilisateur est un simple utilisateur
     */
    public function isRegularUser(): bool
    {
        return $this->role_in_tenant === 'user' && !$this->is_tenant_owner;
    }

    /**
     * Changer le tenant de l'utilisateur
     */
    public function switchTenant($tenantId)
    {
        session()->put('current_tenant_id', (int) $tenantId);

        return $this;
    }

    public function hasTenantAccess(int $tenantId): bool
    {
        if ($tenantId <= 0) {
            return false;
        }

        if (Schema::hasTable('tenant_user_memberships')) {
            $membership = $this->tenantMemberships()
                ->where('tenant_id', $tenantId)
                ->latest('id')
                ->first();

            if ($membership) {
                return (string) $membership->status === 'active';
            }
        }

        return (int) $this->getOriginal('tenant_id') === $tenantId || (int) $this->tenant_id === $tenantId;
    }

    public function tenantRole(?int $tenantId = null): ?Role
    {
        $tenantId = (int) ($tenantId ?? session('current_tenant_id', $this->tenant_id ?: 0));
        if ($tenantId <= 0) {
            return null;
        }

        $membership = $this->tenantMemberships()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();

        if ($membership?->role_id) {
            return Role::query()
                ->where('id', (int) $membership->role_id)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();
        }

        $roleName = (string) ($membership?->role_in_tenant ?: ((int) $this->tenant_id === $tenantId ? $this->role_in_tenant : ''));
        if ($roleName === '') {
            return null;
        }

        return Role::query()
            ->where('tenant_id', $tenantId)
            ->where('name', $roleName)
            ->where('is_active', true)
            ->first();
    }

    public function hasTenantRole(array|string $roles, ?int $tenantId = null): bool
    {
        if ($this->hasRole('super_admin') || $this->hasRole('super-admin')) {
            return true;
        }

        $role = $this->tenantRole($tenantId);
        if (!$role) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($role->name, $roles, true);
    }

    public function hasTenantPermission(array|string $permissions, ?int $tenantId = null): bool
    {
        if ($this->hasRole('super_admin') || $this->hasRole('super-admin')) {
            return true;
        }

        $role = $this->tenantRole($tenantId);
        if (!$role) {
            return false;
        }

        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $granted = $role->permissions()->pluck('name')->all();

        foreach ($permissions as $permission) {
            if (!in_array($permission, $granted, true)) {
                return false;
            }
        }

        return true;
    }

    public function membershipForTenant(int $tenantId): ?TenantUserMembership
    {
        if (!Schema::hasTable('tenant_user_memberships') || $tenantId <= 0) {
            return null;
        }

        return $this->tenantMemberships()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();
    }

    public function ensureTenantMembership(?int $tenantId = null, ?string $role = null, ?bool $isOwner = null): void
    {
        if (!Schema::hasTable('tenant_user_memberships')) {
            return;
        }

        $tenantId = (int) ($tenantId ?? $this->tenant_id ?? 0);
        if ($tenantId <= 0 || !$this->exists) {
            return;
        }

        TenantUserMembership::query()->updateOrCreate(
            [
                'user_id' => (int) $this->id,
                'tenant_id' => $tenantId,
            ],
            [
                'role_in_tenant' => (string) ($role ?? $this->role_in_tenant ?? 'user'),
                'is_tenant_owner' => (bool) ($isOwner ?? $this->is_tenant_owner ?? false),
                'status' => 'active',
                'joined_at' => now(),
            ]
        );
    }

    /**
     * Mettre à jour le dernier accès
     */
    public function updateLastAccess($ip = null)
    {
        $this->last_login_at = now();
        $this->last_login_ip = $ip;
        $this->save();
        
        return $this;
    }

    // ==================== OVERRIDES ====================
    
    /**
     * Surcharge pour créer automatiquement le tenant_id
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            if (session()->has('current_tenant_id') && !$user->tenant_id) {
                $user->tenant_id = session('current_tenant_id');
            }
        });

        static::created(function (self $user): void {
            $user->ensureTenantMembership();
        });

        static::updated(function (self $user): void {
            if ($user->wasChanged('tenant_id') || $user->wasChanged('role_in_tenant') || $user->wasChanged('is_tenant_owner')) {
                $user->ensureTenantMembership();
            }
        });
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new AccountActivationNotification());
    }
}
