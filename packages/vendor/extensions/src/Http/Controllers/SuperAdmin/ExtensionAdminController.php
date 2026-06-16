<?php

namespace Vendor\Extensions\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Extensions\Http\Requests\ExtensionRequest;
use Vendor\Extensions\Services\ExtensionService;
use Vendor\Extensions\Exports\ExtensionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExtensionAdminController extends Controller
{
    public function __construct(protected ExtensionService $service) {}

    /* ── INDEX ────────────────────────────────────────────────────────────── */

    public function index()
    {
        return view('extensions::superadmin.index', [
            'categories'    => config('extensions.categories', []),
            'statuses'      => config('extensions.extension_statuses', []),
            'pricingTypes'  => config('extensions.pricing_types', []),
        ]);
    }

    /* ── CREATE ───────────────────────────────────────────────────────────── */

    public function create()
    {
        return view('extensions::superadmin.form', [
            'extension'     => null,
            'categories'    => config('extensions.categories', []),
            'statuses'      => config('extensions.extension_statuses', []),
            'pricingTypes'  => config('extensions.pricing_types', []),
            'billingCycles' => config('extensions.billing_cycles', []),
        ]);
    }

    /* ── STORE ────────────────────────────────────────────────────────────── */

    public function store(ExtensionRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Gérer les uploads
            if ($request->hasFile('icon_file')) {
                $data['icon_file'] = $request->file('icon_file');
            }
            if ($request->hasFile('banner_file')) {
                $data['banner_file'] = $request->file('banner_file');
            }

            // Booleans
            $data['is_featured'] = $request->boolean('is_featured');
            $data['is_new']      = $request->boolean('is_new');
            $data['is_verified'] = $request->boolean('is_verified');
            $data['is_official'] = $request->boolean('is_official');
            $data['has_trial']   = $request->boolean('has_trial');

            $extension = $this->service->createExtension($data);

            return response()->json([
                'success'  => true,
                'message'  => __('extensions::extensions.messages.extension_created', ['name' => $extension->name]),
                'data'     => $extension,
                'redirect' => route('superadmin.extensions.show', $extension),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── SHOW ─────────────────────────────────────────────────────────────── */

    public function show(Extension $extension)
    {
        $extension->loadCount(['tenantExtensions', 'activeTenants', 'approvedReviews'])
                  ->load(['approvedReviews.tenant', 'tenantExtensions.tenant']);

        $activations = TenantExtension::where('extension_id', $extension->id)
            ->with(['tenant', 'activatedByUser'])
            ->latest()
            ->paginate(15);

        return view('extensions::superadmin.show', compact('extension', 'activations'));
    }

    /* ── EDIT ─────────────────────────────────────────────────────────────── */

    public function edit(Extension $extension)
    {
        return view('extensions::superadmin.form', [
            'extension'     => $extension,
            'categories'    => config('extensions.categories', []),
            'statuses'      => config('extensions.extension_statuses', []),
            'pricingTypes'  => config('extensions.pricing_types', []),
            'billingCycles' => config('extensions.billing_cycles', []),
        ]);
    }

    /* ── UPDATE ───────────────────────────────────────────────────────────── */

    public function update(ExtensionRequest $request, Extension $extension): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('icon_file'))   $data['icon_file']   = $request->file('icon_file');
            if ($request->hasFile('banner_file'))  $data['banner_file'] = $request->file('banner_file');

            // Keep current media if edit form leaves them empty.
            if (($data['icon'] ?? null) === null && !$request->hasFile('icon_file')) {
                unset($data['icon']);
            }

            $data['is_featured'] = $request->boolean('is_featured');
            $data['is_new']      = $request->boolean('is_new');
            $data['is_verified'] = $request->boolean('is_verified');
            $data['is_official'] = $request->boolean('is_official');
            $data['has_trial']   = $request->boolean('has_trial');

            $extension = $this->service->updateExtension($extension, $data);

            return response()->json([
                'success'  => true,
                'message'  => __('extensions::extensions.messages.extension_updated'),
                'data'     => $extension,
                'redirect' => route('superadmin.extensions.show', $extension),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── DESTROY ──────────────────────────────────────────────────────────── */

    public function destroy(Extension $extension): JsonResponse
    {
        try {
            $this->service->deleteExtension($extension);
            return response()->json(['success' => true, 'message' => __('extensions::extensions.messages.extension_deleted')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── DATA / STATS AJAX ────────────────────────────────────────────────── */

    public function getData(Request $request): JsonResponse
    {
        $filters = $request->all();
        $perPage = min((int)($filters['per_page'] ?? 20), 100);
        $data    = $this->service->getStats(); // préchargé
        $extensions = Extension::filter($filters)
            ->withCount(['tenantExtensions as installs', 'activeTenants as active_installs'])
            ->paginate($perPage);

        return response()->json([
            'data'         => $extensions->map(fn($e) => $this->formatExtension($e))->values(),
            'current_page' => $extensions->currentPage(),
            'last_page'    => $extensions->lastPage(),
            'per_page'     => $extensions->perPage(),
            'total'        => $extensions->total(),
            'from'         => $extensions->firstItem(),
            'to'           => $extensions->lastItem(),
        ]);
    }

    public function getStats(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->getStats()]);
    }

    /* ── ACTIONS ──────────────────────────────────────────────────────────── */

    public function toggleFeatured(Extension $extension): JsonResponse
    {
        try {
            $extension = $this->service->toggleFeatured($extension);
            return response()->json([
                'success' => true,
                'message' => $extension->is_featured ? __('extensions::extensions.messages.featured_enabled') : __('extensions::extensions.messages.featured_disabled'),
                'value'   => $extension->is_featured,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function toggleStatus(Extension $extension): JsonResponse
    {
        try {
            $extension = $this->service->toggleStatus($extension);
            return response()->json([
                'success' => true,
                'message' => __('extensions::extensions.messages.status_changed', ['status' => $extension->status_label]),
                'status'  => $extension->status,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── GESTION ACTIVATIONS ──────────────────────────────────────────────── */

    public function activationsIndex()
    {
        return view('extensions::superadmin.activations', [
            'activationStatuses' => config('extensions.activation_statuses', []),
        ]);
    }

    public function activationsData(Request $request): JsonResponse
    {
        $activations = $this->service->getAllActivations($request->all());

        return response()->json([
            'data'         => $activations->items(),
            'current_page' => $activations->currentPage(),
            'last_page'    => $activations->lastPage(),
            'per_page'     => $activations->perPage(),
            'total'        => $activations->total(),
        ]);
    }

    public function suspendActivation(Request $request, TenantExtension $activation): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:255']);
        try {
            $this->service->suspend($activation, $request->reason, auth()->user()->name ?? 'Super Admin');
            return response()->json(['success' => true, 'message' => __('extensions::extensions.messages.suspended')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function restoreActivation(TenantExtension $activation): JsonResponse
    {
        try {
            $activation->update(['status' => 'active', 'suspended_at' => null, 'suspension_reason' => null]);
            return response()->json(['success' => true, 'message' => __('extensions::extensions.messages.restored')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── EXPORT ───────────────────────────────────────────────────────────── */

    public function exportExcel()
    {
        return Excel::download(new ExtensionsExport, 'extensions_' . date('Y-m-d') . '.xlsx');
    }

    /* ── Helper ───────────────────────────────────────────────────────────── */

    private function formatExtension(Extension $e): array
    {
        return [
            'id'              => $e->id,
            'slug'            => $e->slug,
            'name'            => $e->name,
            'tagline'         => $e->tagline,
            'category'        => $e->category,
            'category_label'  => $e->category_label,
            'category_icon'   => $e->category_icon,
            'category_color'  => $e->category_color,
            'icon'            => $e->icon_class,
            'icon_url'        => $e->icon_url,
            'icon_bg_color'   => $e->icon_bg_color,
            'pricing_type'    => $e->pricing_type,
            'pricing_label'   => $e->pricing_label,
            'price'           => $e->price,
            'currency'        => $e->currency,
            'is_free'         => $e->is_free,
            'status'          => $e->status,
            'status_label'    => $e->status_label,
            'is_featured'     => $e->is_featured,
            'is_new'          => $e->is_new,
            'is_official'     => $e->is_official,
            'is_verified'     => $e->is_verified,
            'installs'        => $e->installs ?? $e->installs_count,
            'active_installs' => $e->active_installs ?? $e->active_installs_count,
            'rating'          => $e->rating,
            'version'         => $e->version,
            'show_url'        => route('superadmin.extensions.show', $e),
            'edit_url'        => route('superadmin.extensions.edit', $e),
            'featured_url'    => route('superadmin.extensions.featured', $e),
            'status_url'      => route('superadmin.extensions.status', $e),
            'delete_url'      => route('superadmin.extensions.destroy', $e),
        ];
    }
}
