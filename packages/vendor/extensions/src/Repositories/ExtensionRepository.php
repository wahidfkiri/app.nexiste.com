<?php

namespace Vendor\Extensions\Repositories;

use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Extensions\Models\ExtensionActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExtensionRepository
{
    // ── Catalogue (super-admin) ─────────────────────────────────────────────

    public function allPaginated(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Extension::filter($filters)
            ->withCount(['tenantExtensions', 'activeTenants', 'approvedReviews'])
            ->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Extension
    {
        return Extension::withCount(['tenantExtensions', 'activeTenants'])
            ->with(['approvedReviews'])
            ->where('slug', $slug)
            ->first();
    }

    public function findById(int $id): ?Extension
    {
        return Extension::withCount(['tenantExtensions', 'activeTenants'])
            ->find($id);
    }

    public function create(array $data): Extension
    {
        return Extension::create($data);
    }

    public function update(Extension $extension, array $data): Extension
    {
        $extension->update($data);
        return $extension->fresh();
    }

    public function delete(Extension $extension): bool
    {
        return (bool) $extension->delete();
    }

    public function getStats(): array
    {
        return [
            'total'           => Extension::count(),
            'active'          => Extension::where('status', 'active')->count(),
            'featured'        => Extension::where('is_featured', true)->count(),
            'free'            => Extension::where('pricing_type', 'free')->count(),
            'paid'            => Extension::whereIn('pricing_type', ['paid','freemium','per_seat','usage'])->count(),
            'total_installs'  => TenantExtension::whereIn('status', ['active','trial'])->count(),
            'total_revenue'   => TenantExtension::whereIn('status', ['active'])
                                    ->sum('price_paid'),
            'by_category'     => Extension::active()
                                    ->select('category', DB::raw('count(*) as count'))
                                    ->groupBy('category')
                                    ->pluck('count', 'category')
                                    ->toArray(),
        ];
    }

    // ── Marketplace (tenant view) ───────────────────────────────────────────

    public function getMarketplace(array $filters, int $tenantId, int $perPage = 20): LengthAwarePaginator
    {
        return Extension::active()
            ->filter($filters)
            ->withCount(['tenantExtensions'])
            ->with(['tenantExtensions' => fn($q) => $q->where('tenant_id', $tenantId)])
            ->paginate($perPage);
    }

    public function getFeaturedExtensions(): Collection
    {
        return Extension::active()->featured()->orderBy('sort_order')->limit(6)->get();
    }

    public function getNewExtensions(): Collection
    {
        return Extension::active()->where('is_new', true)->latest()->limit(6)->get();
    }

    public function getByCategory(string $category, int $limit = 8): Collection
    {
        return Extension::active()->byCategory($category)->orderBy('sort_order')->limit($limit)->get();
    }

    // ── Activations tenant ──────────────────────────────────────────────────

    public function getTenantExtensions(int $tenantId, array $filters = []): Collection
    {
        return TenantExtension::where('tenant_id', $tenantId)
            ->whereHas('extension', fn($query) => $query->where('status', 'active'))
            ->with(['extension' => fn($query) => $query->where('status', 'active')])
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->latest('updated_at')
            ->get();
    }

    public function getTenantActivation(int $tenantId, int $extensionId): ?TenantExtension
    {
        return TenantExtension::where('tenant_id', $tenantId)
            ->where('extension_id', $extensionId)
            ->with('extension')
            ->first();
    }

    public function createActivation(array $data): TenantExtension
    {
        return TenantExtension::create($data);
    }

    public function updateActivation(TenantExtension $activation, array $data): TenantExtension
    {
        $activation->update($data);
        return $activation->fresh(['extension']);
    }

    // ── SuperAdmin: toutes les activations ──────────────────────────────────

    public function getAllActivationsPaginated(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = TenantExtension::with(['extension', 'tenant', 'activatedByUser']);

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['extension_id'])) {
            $query->where('extension_id', $filters['extension_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    // ── Logs ────────────────────────────────────────────────────────────────

    public function logActivity(array $data): ExtensionActivityLog
    {
        return ExtensionActivityLog::create(array_merge($data, [
            'ip_address' => request()->ip(),
        ]));
    }
}
