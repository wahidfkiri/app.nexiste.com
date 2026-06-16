<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TenantUserMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Vendor\CrmCore\Models\Tenant;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Rbac\Services\TenantRoleService;

class TenantAdminController extends Controller
{
    private const STATUSES = [
        'active' => 'Actif',
        'suspended' => 'Suspendu',
        'pending' => 'En attente',
    ];

    public function index(): View
    {
        return view('superadmin.tenants.index', [
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(Tenant $tenant): View
    {
        return view('superadmin.tenants.show', [
            'tenant' => $tenant,
            'statuses' => self::STATUSES,
            'counters' => $this->tenantCounters($tenant),
            'members' => $this->tenantMembers($tenant),
            'activations' => $this->tenantActivations($tenant),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $perPage = max(5, min((int) $request->input('per_page', 20), 100));
        $query = $this->tenantQuery($request);

        $tenants = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tenants->getCollection()
                ->map(fn (Tenant $tenant): array => $this->formatTenant($tenant))
                ->values(),
            'current_page' => $tenants->currentPage(),
            'last_page' => $tenants->lastPage(),
            'per_page' => $tenants->perPage(),
            'total' => $tenants->total(),
            'from' => $tenants->firstItem(),
            'to' => $tenants->lastItem(),
        ]);
    }

    public function getStats(): JsonResponse
    {
        $now = now();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => Tenant::query()->count(),
                'active' => Tenant::query()->where('status', 'active')->count(),
                'suspended' => Tenant::query()->where('status', 'suspended')->count(),
                'pending' => Tenant::query()->where('status', 'pending')->count(),
                'subscribed' => Tenant::query()
                    ->whereNotNull('subscription_ends_at')
                    ->where('subscription_ends_at', '>', $now)
                    ->count(),
                'active_members' => $this->activeMembersAcrossActiveTenants(),
            ],
        ]);
    }

    public function store(Request $request, TenantRoleService $tenantRoleService): JsonResponse
    {
        $data = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'tenant_email' => ['required', 'email', 'max:255', Rule::unique('tenants', 'email')],
            'tenant_phone' => ['nullable', 'string', 'max:60'],
            'tenant_address' => ['nullable', 'string', 'max:1000'],
            'tenant_status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'timezone' => ['required', 'string', 'max:80'],
            'locale' => ['required', 'string', 'max:10'],
            'currency' => ['required', 'string', 'max:8'],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $adminEmail = mb_strtolower(trim((string) $data['admin_email']));
        $existingAdmin = User::withTrashed()->whereRaw('LOWER(email) = ?', [$adminEmail])->first();

        if ($existingAdmin?->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet email appartient à un utilisateur supprimé. Restaurez le compte ou utilisez un autre email.',
                'errors' => [
                    'admin_email' => ['Cet email appartient à un utilisateur supprimé.'],
                ],
            ], 422);
        }

        if (!$existingAdmin && empty($data['admin_password'])) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe est obligatoire pour créer un nouvel administrateur.',
                'errors' => [
                    'admin_password' => ['Le mot de passe est obligatoire pour créer un nouvel administrateur.'],
                ],
            ], 422);
        }

        [$tenant, $admin] = DB::transaction(function () use ($data, $adminEmail, $existingAdmin, $tenantRoleService): array {
            $tenant = Tenant::query()->create([
                'name' => $data['tenant_name'],
                'slug' => Tenant::generateSlug($data['tenant_name']),
                'email' => mb_strtolower(trim((string) $data['tenant_email'])),
                'phone' => $data['tenant_phone'] ?? null,
                'address' => $data['tenant_address'] ?? null,
                'timezone' => $data['timezone'] ?: 'Europe/Paris',
                'locale' => $data['locale'] ?: 'fr',
                'currency' => mb_strtoupper((string) ($data['currency'] ?: 'EUR')),
                'status' => $data['tenant_status'],
                'trial_ends_at' => $data['trial_ends_at'] ?? null,
                'subscription_ends_at' => $data['subscription_ends_at'] ?? null,
            ]);

            $tenantRoleService->ensureTenantRoles((int) $tenant->id);

            $admin = $existingAdmin;

            if (!$admin) {
                $admin = User::query()->create([
                    'name' => $data['admin_name'],
                    'email' => $adminEmail,
                    'password' => Hash::make((string) $data['admin_password']),
                    'tenant_id' => (int) $tenant->id,
                    'role_in_tenant' => 'owner',
                    'is_tenant_owner' => true,
                    'status' => 'active',
                    'is_active' => true,
                ]);

                $admin->forceFill([
                    'email_verified_at' => now(),
                ])->save();
            } else {
                $admin->forceFill([
                    'name' => $admin->name ?: $data['admin_name'],
                    'status' => 'active',
                    'is_active' => true,
                    'email_verified_at' => $admin->email_verified_at ?: now(),
                ])->save();
            }

            $tenantRoleService->syncUserRole($admin, (int) $tenant->id, 'owner', [
                'role_in_tenant' => 'owner',
                'is_tenant_owner' => true,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return [$tenant->fresh(), $admin->fresh()];
        });

        return response()->json([
            'success' => true,
            'message' => 'Tenant créé avec succès et administrateur assigné.',
            'data' => [
                'tenant' => $this->formatTenant($tenant),
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                ],
            ],
            'redirect' => route('superadmin.tenants.show', $tenant),
        ], 201);
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tenants', 'email')->ignore($tenant->id)],
            'phone' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['required', 'string', 'max:80'],
            'locale' => ['required', 'string', 'max:10'],
            'currency' => ['required', 'string', 'max:8'],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        $tenant->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Tenant mis à jour avec succès.',
            'data' => $this->formatTenant($tenant->fresh()),
            'redirect' => route('superadmin.tenants.show', $tenant),
        ]);
    }

    public function updateStatus(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
        ]);

        $tenant->update(['status' => $data['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Statut du tenant mis à jour.',
            'data' => $this->formatTenant($tenant->fresh()),
        ]);
    }

    private function tenantQuery(Request $request): Builder
    {
        $query = Tenant::query()->select('tenants.*');
        $this->appendComputedCounts($query);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $status = (string) $request->input('status', 'active');
        if ($status !== 'all' && array_key_exists($status, self::STATUSES)) {
            $query->where('status', $status);
        }

        $sortBy = (string) $request->input('sort_by', 'created_at');
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortMap = [
            'name' => 'tenants.name',
            'email' => 'tenants.email',
            'status' => 'tenants.status',
            'created_at' => 'tenants.created_at',
            'subscription_ends_at' => 'tenants.subscription_ends_at',
        ];
        $computedSorts = ['members_count', 'active_members_count', 'active_apps_count', 'clients_count'];

        if (isset($sortMap[$sortBy])) {
            $query->orderBy($sortMap[$sortBy], $sortDir);
        } elseif (in_array($sortBy, $computedSorts, true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('tenants.created_at');
        }

        return $query;
    }

    private function appendComputedCounts(Builder $query): void
    {
        if ($this->hasMembershipsTable()) {
            $query->selectSub(
                DB::table('tenant_user_memberships')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('tenant_user_memberships.tenant_id', 'tenants.id'),
                'members_count'
            )->selectSub(
                DB::table('tenant_user_memberships')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('tenant_user_memberships.tenant_id', 'tenants.id')
                    ->where('tenant_user_memberships.status', 'active'),
                'active_members_count'
            );
        } elseif (Schema::hasTable('users') && Schema::hasColumn('users', 'tenant_id')) {
            $activeUsersQuery = DB::table('users')
                ->selectRaw('COUNT(*)')
                ->whereColumn('users.tenant_id', 'tenants.id');

            if (Schema::hasColumn('users', 'status')) {
                $activeUsersQuery->where('users.status', 'active');
            }

            $query->selectSub(
                DB::table('users')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('users.tenant_id', 'tenants.id'),
                'members_count'
            )->selectSub($activeUsersQuery, 'active_members_count');
        } else {
            $query->selectRaw('0 as members_count')->selectRaw('0 as active_members_count');
        }

        if ($this->hasTenantExtensionsTable()) {
            $query->selectSub(
                DB::table('tenant_extensions')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('tenant_extensions.tenant_id', 'tenants.id'),
                'apps_count'
            )->selectSub(
                DB::table('tenant_extensions')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('tenant_extensions.tenant_id', 'tenants.id')
                    ->whereIn('tenant_extensions.status', ['active', 'trial']),
                'active_apps_count'
            );
        } else {
            $query->selectRaw('0 as apps_count')->selectRaw('0 as active_apps_count');
        }

        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'tenant_id')) {
            $query->selectSub(
                DB::table('clients')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('clients.tenant_id', 'tenants.id'),
                'clients_count'
            );
        } else {
            $query->selectRaw('0 as clients_count');
        }
    }

    private function tenantCounters(Tenant $tenant): array
    {
        return [
            'members' => $this->membershipCount($tenant),
            'active_members' => $this->membershipCount($tenant, 'active'),
            'apps' => $this->tenantExtensionsCount($tenant),
            'active_apps' => $this->tenantExtensionsCount($tenant, ['active', 'trial']),
            'clients' => $this->clientCount($tenant),
        ];
    }

    private function tenantMembers(Tenant $tenant): Collection
    {
        if ($this->hasMembershipsTable()) {
            return TenantUserMembership::query()
                ->where('tenant_id', $tenant->id)
                ->with('user:id,name,email,status,is_active,last_login_at')
                ->orderByDesc('is_tenant_owner')
                ->latest('joined_at')
                ->limit(16)
                ->get()
                ->map(function (TenantUserMembership $membership): object {
                    return (object) [
                        'name' => (string) ($membership->user?->name ?? 'Utilisateur supprimé'),
                        'email' => (string) ($membership->user?->email ?? ''),
                        'role' => (string) ($membership->role_in_tenant ?? 'user'),
                        'status' => (string) ($membership->status ?? 'active'),
                        'is_owner' => (bool) $membership->is_tenant_owner,
                        'joined_at' => $membership->joined_at,
                    ];
                });
        }

        return $tenant->users()
            ->latest()
            ->limit(16)
            ->get(['id', 'name', 'email', 'status', 'is_tenant_owner', 'created_at'])
            ->map(function ($user): object {
                return (object) [
                    'name' => (string) $user->name,
                    'email' => (string) $user->email,
                    'role' => (string) ($user->role_in_tenant ?? 'user'),
                    'status' => (string) ($user->status ?? 'active'),
                    'is_owner' => (bool) ($user->is_tenant_owner ?? false),
                    'joined_at' => $user->created_at,
                ];
            });
    }

    private function tenantActivations(Tenant $tenant): Collection
    {
        if (!$this->hasTenantExtensionsTable()) {
            return collect();
        }

        return TenantExtension::query()
            ->where('tenant_id', $tenant->id)
            ->with('extension:id,slug,name,category,icon,icon_bg_color,status')
            ->latest()
            ->limit(16)
            ->get();
    }

    private function formatTenant(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => (string) $tenant->name,
            'slug' => (string) $tenant->slug,
            'email' => (string) $tenant->email,
            'phone' => (string) ($tenant->phone ?? ''),
            'status' => (string) $tenant->status,
            'status_label' => self::STATUSES[$tenant->status] ?? ucfirst((string) $tenant->status),
            'status_class' => $this->statusClass((string) $tenant->status),
            'members_count' => (int) ($tenant->members_count ?? 0),
            'active_members_count' => (int) ($tenant->active_members_count ?? 0),
            'apps_count' => (int) ($tenant->apps_count ?? 0),
            'active_apps_count' => (int) ($tenant->active_apps_count ?? 0),
            'clients_count' => (int) ($tenant->clients_count ?? 0),
            'trial_ends_at' => $tenant->trial_ends_at?->format('d/m/Y'),
            'subscription_ends_at' => $tenant->subscription_ends_at?->format('d/m/Y'),
            'created_at' => $tenant->created_at?->format('d/m/Y'),
            'created_at_iso' => $tenant->created_at?->toIso8601String(),
            'show_url' => route('superadmin.tenants.show', $tenant),
        ];
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'active' => 'actif',
            'pending' => 'en_attente',
            'suspended' => 'suspendu',
            default => 'inactif',
        };
    }

    private function activeMembersAcrossActiveTenants(): int
    {
        if ($this->hasMembershipsTable()) {
            return (int) DB::table('tenant_user_memberships')
                ->join('tenants', 'tenants.id', '=', 'tenant_user_memberships.tenant_id')
                ->where('tenants.status', 'active')
                ->where('tenant_user_memberships.status', 'active')
                ->count();
        }

        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'tenant_id')) {
            return 0;
        }

        $query = DB::table('users')
            ->join('tenants', 'tenants.id', '=', 'users.tenant_id')
            ->where('tenants.status', 'active');

        if (Schema::hasColumn('users', 'status')) {
            $query->where('users.status', 'active');
        }

        return (int) $query->count();
    }

    private function membershipCount(Tenant $tenant, ?string $status = null): int
    {
        if ($this->hasMembershipsTable()) {
            $query = DB::table('tenant_user_memberships')->where('tenant_id', $tenant->id);
            if ($status !== null) {
                $query->where('status', $status);
            }

            return (int) $query->count();
        }

        $query = $tenant->users();
        if ($status !== null && Schema::hasColumn('users', 'status')) {
            $query->where('status', $status);
        }

        return (int) $query->count();
    }

    private function tenantExtensionsCount(Tenant $tenant, string|array|null $status = null): int
    {
        if (!$this->hasTenantExtensionsTable()) {
            return 0;
        }

        $query = DB::table('tenant_extensions')->where('tenant_id', $tenant->id);
        if (is_array($status)) {
            $query->whereIn('status', $status);
        } elseif ($status !== null) {
            $query->where('status', $status);
        }

        return (int) $query->count();
    }

    private function clientCount(Tenant $tenant): int
    {
        if (!Schema::hasTable('clients') || !Schema::hasColumn('clients', 'tenant_id')) {
            return 0;
        }

        return (int) DB::table('clients')->where('tenant_id', $tenant->id)->count();
    }

    private function hasMembershipsTable(): bool
    {
        return Schema::hasTable('tenant_user_memberships');
    }

    private function hasTenantExtensionsTable(): bool
    {
        return class_exists(TenantExtension::class) && Schema::hasTable('tenant_extensions');
    }
}
