<?php

namespace Vendor\User\Repositories;

use App\Models\TenantUserMembership;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Vendor\User\Models\UserInvitation;

class UserRepository
{
    public function getFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $query = $this->baseQueryForTenant($tenantId)->with(['roles']);

        if (!empty($filters['search'])) {
            $term = (string) $filters['search'];
            $query->where(function ($q) use ($term): void {
                $q->where('users.name', 'like', "%{$term}%")
                    ->orWhere('users.email', 'like', "%{$term}%")
                    ->orWhere('users.job_title', 'like', "%{$term}%")
                    ->orWhere('users.department', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('name', (string) $filters['role']));
        }

        if (!empty($filters['status'])) {
            if (Schema::hasTable('tenant_user_memberships')) {
                $query->where('tum.status', (string) $filters['status']);
            } else {
                $query->where('users.status', (string) $filters['status']);
            }
        }

        if (!empty($filters['role_in_tenant'])) {
            if (Schema::hasTable('tenant_user_memberships')) {
                $query->where('tum.role_in_tenant', (string) $filters['role_in_tenant']);
            } else {
                $query->where('users.role_in_tenant', (string) $filters['role_in_tenant']);
            }
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'name' => 'users.name',
            'email' => 'users.email',
            'created_at' => 'users.created_at',
            'last_login_at' => 'users.last_login_at',
            'status' => Schema::hasTable('tenant_user_memberships') ? 'tum.status' : 'users.status',
            'role_in_tenant' => Schema::hasTable('tenant_user_memberships') ? 'tum.role_in_tenant' : 'users.role_in_tenant',
        ];

        $query->orderBy($sortable[$sortBy] ?? 'users.created_at', $sortDir);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        $tenantId = (int) Auth::user()->tenant_id;

        return $this->baseQueryForTenant($tenantId)
            ->with('roles')
            ->where('users.id', $id)
            ->first();
    }

    public function count(): int
    {
        $tenantId = (int) Auth::user()->tenant_id;

        return (int) $this->baseQueryForTenant($tenantId)->count('users.id');
    }

    public function countByStatus(string $status): int
    {
        $tenantId = (int) Auth::user()->tenant_id;

        return (int) $this->baseQueryForTenant($tenantId)
            ->where(Schema::hasTable('tenant_user_memberships') ? 'tum.status' : 'users.status', $status)
            ->count('users.id');
    }

    public function countByRole(): array
    {
        $tenantId = (int) Auth::user()->tenant_id;

        if (Schema::hasTable('tenant_user_memberships')) {
            return TenantUserMembership::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->selectRaw('role_in_tenant, count(*) as count')
                ->groupBy('role_in_tenant')
                ->pluck('count', 'role_in_tenant')
                ->toArray();
        }

        return User::query()
            ->where('tenant_id', $tenantId)
            ->selectRaw('role_in_tenant, count(*) as count')
            ->groupBy('role_in_tenant')
            ->pluck('count', 'role_in_tenant')
            ->toArray();
    }

    public function update(User $user, array $data): User
    {
        $tenantId = (int) Auth::user()->tenant_id;

        if (Schema::hasTable('tenant_user_memberships')) {
            $role = $data['role_in_tenant'] ?? null;
            $roleId = $data['role_id'] ?? null;
            $status = $data['status'] ?? null;
            $membershipAttributes = [];

            if ($role !== null) {
                $membershipAttributes['role_in_tenant'] = (string) $role;
            }

            if ($roleId !== null) {
                $membershipAttributes['role_id'] = (int) $roleId;
            }

            if ($status !== null) {
                $membershipAttributes['status'] = (string) $status;
            }

            if ($membershipAttributes !== []) {
                TenantUserMembership::query()->updateOrCreate(
                    ['user_id' => (int) $user->id, 'tenant_id' => $tenantId],
                    $membershipAttributes
                );
            }

            unset($data['role_in_tenant']);
            unset($data['is_tenant_owner']);
            unset($data['role_id']);
            unset($data['status']);

            if ((int) $user->getOriginal('tenant_id') === $tenantId && $role !== null) {
                $data['role_in_tenant'] = (string) $role;
            }
        }

        $user->update($data);
        $this->syncAccountStatusFromMemberships([(int) $user->id]);
        $fresh = $user->fresh(['roles']);

        if ($fresh && Schema::hasTable('tenant_user_memberships')) {
            $membership = TenantUserMembership::query()
                ->where('user_id', (int) $fresh->id)
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->first();

            if ($membership) {
                $fresh->setAttribute('role_in_tenant', (string) $membership->role_in_tenant);
                $fresh->setAttribute('is_tenant_owner', (bool) $membership->is_tenant_owner);
                $fresh->setAttribute('status', (string) $membership->status);
            }
        }

        return $fresh;
    }

    public function delete(User $user): bool
    {
        $tenantId = (int) Auth::user()->tenant_id;

        if (Schema::hasTable('tenant_user_memberships')) {
            $membership = TenantUserMembership::query()
                ->where('user_id', (int) $user->id)
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->first();

            if ($membership && (bool) $membership->is_tenant_owner) {
                throw new \RuntimeException('Impossible de supprimer le proprietaire du compte.');
            }

            TenantUserMembership::query()
                ->where('user_id', (int) $user->id)
                ->where('tenant_id', $tenantId)
                ->delete();

            $tenantRoleIds = Role::query()
                ->where('tenant_id', $tenantId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($tenantRoleIds !== []) {
                $user->roles()->detach($tenantRoleIds);
            }

            $remaining = TenantUserMembership::query()
                ->where('user_id', (int) $user->id)
                ->where('status', 'active')
                ->orderByDesc('is_tenant_owner')
                ->orderBy('id')
                ->first();

            if (!$remaining) {
                $emailHash = substr(sha1((string) $user->email . '|' . (string) $user->id . '|' . microtime(true)), 0, 16);
                $archivedEmail = "deleted-{$user->id}-{$emailHash}@archived.local";

                $user->forceFill([
                    'email' => mb_strtolower($archivedEmail),
                    'is_active' => false,
                    'status' => 'inactive',
                ])->save();

                return (bool) $user->delete();
            }

            if ((int) $user->getOriginal('tenant_id') === $tenantId) {
                $user->forceFill([
                    'tenant_id' => (int) $remaining->tenant_id,
                    'role_in_tenant' => (string) $remaining->role_in_tenant,
                    'is_tenant_owner' => (bool) $remaining->is_tenant_owner,
                ])->save();
            }

            $this->syncAccountStatusFromMemberships([(int) $user->id]);

            return true;
        }

        if ((bool) $user->is_tenant_owner) {
            throw new \RuntimeException('Impossible de supprimer le proprietaire du compte.');
        }

        return (bool) $user->delete();
    }

    public function bulkDelete(array $ids): int
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $authId = (int) Auth::id();

        if (Schema::hasTable('tenant_user_memberships')) {
            $memberships = TenantUserMembership::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('user_id', $ids)
                ->where('user_id', '!=', $authId)
                ->where('is_tenant_owner', false)
                ->get(['user_id']);

            $deleted = 0;
            foreach ($memberships as $membership) {
                $target = User::find((int) $membership->user_id);
                if (!$target) {
                    continue;
                }
                if ($this->delete($target)) {
                    $deleted++;
                }
            }

            return $deleted;
        }

        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->where('is_tenant_owner', false)
            ->where('id', '!=', $authId)
            ->delete();
    }

    public function bulkStatusUpdate(array $ids, string $status): int
    {
        $tenantId = (int) Auth::user()->tenant_id;
        $authId = (int) Auth::id();

        if (Schema::hasTable('tenant_user_memberships')) {
            $memberships = TenantUserMembership::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('user_id', $ids)
                ->where('user_id', '!=', $authId)
                ->where('is_tenant_owner', false)
                ->get(['user_id']);

            if ($memberships->isEmpty()) {
                return 0;
            }

            $userIds = $memberships->pluck('user_id')->map(fn ($id) => (int) $id)->all();

            TenantUserMembership::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('user_id', $userIds)
                ->update(['status' => $status]);

            $this->syncAccountStatusFromMemberships($userIds);

            return count($userIds);
        }

        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->where('is_tenant_owner', false)
            ->where('id', '!=', $authId)
            ->update(['status' => $status]);
    }

    public function getInvitations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $tenantId = (int) Auth::user()->tenant_id;

        $query = UserInvitation::query()
            ->where('tenant_id', $tenantId)
            ->with('invitedBy');

        if (!empty($filters['search'])) {
            $query->where('email', 'like', '%' . (string) $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findInvitationByToken(string $token): ?UserInvitation
    {
        $invitation = UserInvitation::query()
            ->with(['tenant', 'invitedBy', 'role', 'invitedUser'])
            ->where('token', $token)
            ->first();

        if ($invitation) {
            $invitation->markExpiredIfNeeded();
        }

        return $invitation?->fresh(['tenant', 'invitedBy', 'role', 'invitedUser']);
    }

    public function createInvitation(array $data): UserInvitation
    {
        return UserInvitation::create($data);
    }

    public function revokeInvitation(UserInvitation $invitation, string $reason = ''): UserInvitation
    {
        $invitation->update([
            'status' => UserInvitation::STATUS_REVOKED,
            'revoked_at' => now(),
            'revoked_reason' => $reason,
            'pending_email_key' => null,
        ]);

        return $invitation->fresh();
    }

    public function pendingInvitationForEmail(string $email, int $tenantId): ?UserInvitation
    {
        return UserInvitation::query()
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->where('status', UserInvitation::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->first();
    }

    private function baseQueryForTenant(int $tenantId)
    {
        if (Schema::hasTable('tenant_user_memberships')) {
            return User::query()
                ->join('tenant_user_memberships as tum', function ($join) use ($tenantId): void {
                    $join->on('users.id', '=', 'tum.user_id')
                        ->where('tum.tenant_id', '=', $tenantId);
                })
                ->select([
                    'users.*',
                    'tum.role_in_tenant as role_in_tenant',
                    'tum.is_tenant_owner as is_tenant_owner',
                    'tum.status as status',
                ]);
        }

        return User::query()->where('users.tenant_id', $tenantId)->select('users.*');
    }

    private function syncAccountStatusFromMemberships(array $userIds): void
    {
        if (!Schema::hasTable('tenant_user_memberships') || $userIds === []) {
            return;
        }

        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        $activeUserIds = TenantUserMembership::query()
            ->whereIn('user_id', $userIds)
            ->where('status', 'active')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($activeUserIds !== []) {
            User::query()
                ->whereIn('id', $activeUserIds)
                ->update([
                    'status' => 'active',
                    'is_active' => true,
                ]);
        }

        $inactiveUserIds = array_values(array_diff($userIds, $activeUserIds));
        if ($inactiveUserIds !== []) {
            User::query()
                ->whereIn('id', $inactiveUserIds)
                ->update([
                    'status' => 'inactive',
                    'is_active' => false,
                ]);
        }
    }
}
