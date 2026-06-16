<?php

namespace Vendor\Extensions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Extensions\Services\ExtensionService;
use Throwable;

class MarketplaceController extends Controller
{
    public function __construct(protected ExtensionService $service)
    {
        $this->service->ensureCatalogSeeded();
    }

    /* ── MARKETPLACE INDEX ────────────────────────────────────────────────── */

    public function index()
    {
        $this->service->ensureCatalogSeeded();
        $tenantId = auth()->user()->tenant_id;

        $featured   = $this->service->getMarketplace(['is_featured' => true, 'per_page' => 6], $tenantId);
        $myApps     = $this->service->getTenantExtensions($tenantId, ['status' => 'active']);

        return view('extensions::marketplace.index', [
            'categories'        => config('extensions.categories', []),
            'pricingTypes'      => config('extensions.pricing_types', []),
            'myAppsCount'       => $myApps->count(),
        ]);
    }

    /* ── MES APPLICATIONS ────────────────────────────────────────────────── */

    public function myApps()
    {
        $this->service->ensureCatalogSeeded();
        $tenantId   = auth()->user()->tenant_id;
        $myApps     = $this->service->getTenantExtensions($tenantId);

        return view('extensions::marketplace.my-apps', [
            'activations'       => $myApps,
            'categories'        => config('extensions.categories', []),
            'activationStatuses'=> config('extensions.activation_statuses', []),
        ]);
    }

    /* ── DÉTAIL EXTENSION ─────────────────────────────────────────────────── */

    public function show(string $slug)
    {
        $this->service->ensureCatalogSeeded();
        $tenantId  = auth()->user()->tenant_id;
        $extension = Extension::where('slug', $slug)->active()->firstOrFail();
        $extension->load(['approvedReviews.user', 'approvedReviews.tenant']);

        $activation = $extension->getActivationFor($tenantId);

        return view('extensions::marketplace.show', compact('extension', 'activation'));
    }

    /* ── DATA AJAX ────────────────────────────────────────────────────────── */

    public function getData(Request $request): JsonResponse
    {
        $this->service->ensureCatalogSeeded();
        $tenantId   = auth()->user()->tenant_id;
        $extensions = $this->service->getMarketplace($request->all(), $tenantId);

        return response()->json([
            'data'         => $extensions->map(fn($e) => $this->formatForTenant($e, $tenantId))->values(),
            'current_page' => $extensions->currentPage(),
            'last_page'    => $extensions->lastPage(),
            'per_page'     => $extensions->perPage(),
            'total'        => $extensions->total(),
        ]);
    }

    public function getStats(): JsonResponse
    {
        $this->service->ensureCatalogSeeded();
        $tenantId = auth()->user()->tenant_id;
        $myApps   = $this->service->getTenantExtensions($tenantId);

        return response()->json([
            'success' => true,
            'data' => [
                'total_activated' => $myApps->count(),
                'active'          => $myApps->where('status', 'active')->count(),
                'trial'           => $myApps->where('status', 'trial')->count(),
                'inactive'        => $myApps->where('status', 'inactive')->count(),
            ],
        ]);
    }

    /* ── ACTIVER ──────────────────────────────────────────────────────────── */

    public function activate(Request $request, string $slug): JsonResponse
    {
        $extension = $this->resolveExtension($slug);
        $tenantId = auth()->user()->tenant_id;

        $request->validate([
            'billing_cycle' => 'nullable|in:' . implode(',', array_keys(config('extensions.billing_cycles', []))),
        ]);

        try {
            $activation = $this->service->activate(
                $extension,
                $tenantId,
                auth()->id(),
                $request->only('billing_cycle')
            );

            $msg = $activation->is_trial
                ? __('extensions::extensions.messages.trial_started_named', ['days' => $extension->trial_days])
                : __('extensions::extensions.messages.activated_named', ['name' => $extension->name]);

            return response()->json([
                'success'   => true,
                'message'   => $msg,
                'is_trial'  => $activation->is_trial,
                'status'    => $activation->status,
                'redirect'  => route('marketplace.settings', $extension->slug),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── DÉSACTIVER ───────────────────────────────────────────────────────── */

    public function deactivate(Request $request, string $slug): JsonResponse
    {
        $extension = $this->resolveExtension($slug);
        $tenantId  = auth()->user()->tenant_id;
        $activation = $extension->getActivationFor($tenantId);

        if (!$activation) {
            return response()->json(['success' => false, 'message' => __('extensions::extensions.common.not_activated')], 422);
        }

        try {
            $this->service->deactivate($activation, auth()->id(), $request->get('reason', ''));
            return response()->json([
                'success' => true,
                'message' => __('extensions::extensions.messages.deactivated_named', ['name' => $extension->name]),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── PARAMÈTRES ───────────────────────────────────────────────────────── */

    public function settings(string $slug)
    {
        $extension = $this->resolveExtension($slug);
        $tenantId  = auth()->user()->tenant_id;
        $activation = $extension->getActivationFor($tenantId);

        if (!$activation || !$activation->is_active) {
            return redirect()->route('marketplace.show', $extension->slug)
                ->with('error', __('extensions::extensions.common.activate_first'));
        }

        // Les extensions qui possèdent un module dédié redirigent vers leur écran natif.
        if ($extension->slug === 'clients' && \Route::has('clients.index')) {
            return redirect()->route('clients.index');
        }
        if ($extension->slug === 'stock' && \Route::has('stock.articles.index')) {
            return redirect()->route('stock.articles.index');
        }
        if ($extension->slug === 'invoice' && \Route::has('invoices.index')) {
            return redirect()->route('invoices.index');
        }
        if ($extension->slug === 'projects' && \Route::has('projects.index')) {
            return redirect()->route('projects.index');
        }
        if ($extension->slug === 'notion-workspace' && \Route::has('notion-workspace.index')) {
            return redirect()->route('notion-workspace.index');
        }
        if ($extension->slug === 'trello-integration' && \Route::has('trello-integration.index')) {
            return redirect()->route('trello-integration.index');
        }
        if ($extension->slug === 'google-calendar' && \Route::has('google-calendar.index')) {
            return redirect()->route('google-calendar.index');
        }
        if ($extension->slug === 'google-drive' && \Route::has('google-drive.index')) {
            return redirect()->route('google-drive.index');
        }
        if ($extension->slug === 'dropbox' && \Route::has('dropbox.index')) {
            return redirect()->route('dropbox.index');
        }
        if ($extension->slug === 'google-sheets' && \Route::has('google-sheets.index')) {
            return redirect()->route('google-sheets.index');
        }
        if ($extension->slug === 'google-docx' && \Route::has('google-docx.index')) {
            return redirect()->route('google-docx.index');
        }
        if ($extension->slug === 'google-gmail' && \Route::has('google-gmail.index')) {
            return redirect()->route('google-gmail.index');
        }
        if ($extension->slug === 'google-meet' && \Route::has('google-meet.index')) {
            return redirect()->route('google-meet.index');
        }
        if ($extension->slug === 'chatbot' && \Route::has('chatbot.index')) {
            return redirect()->route('chatbot.index');
        }
        if ($extension->slug === 'slack') {
            $isConnected = false;
            $slackTokenClass = \NexusExtensions\Slack\Models\SlackToken::class;

            if (class_exists($slackTokenClass) && Schema::hasTable('slack_tokens')) {
                $isConnected = $slackTokenClass::query()
                    ->where('tenant_id', (int) $tenantId)
                    ->where('is_active', true)
                    ->exists();
            }

            if ($isConnected && \Route::has('slack.index')) {
                return redirect()->route('slack.index');
            }

            if (\Route::has('slack.oauth.connect')) {
                return redirect()->route('slack.oauth.connect');
            }

            if (\Route::has('slack.index')) {
                return redirect()->route('slack.index');
            }
        }

        return view('extensions::extensions.settings', compact('extension', 'activation'));
    }

    public function saveSettings(Request $request, string $slug): JsonResponse
    {
        $extension = $this->resolveExtension($slug);
        $tenantId  = auth()->user()->tenant_id;
        $activation = $extension->getActivationFor($tenantId);

        if (!$activation || !$activation->is_active) {
            return response()->json(['success' => false, 'message' => __('extensions::extensions.common.not_activated')], 422);
        }

        try {
            $this->service->saveSettings($activation, $request->except(['_token', '_method']));
            return response()->json(['success' => true, 'message' => __('extensions::extensions.messages.settings_saved')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ── Helper ───────────────────────────────────────────────────────────── */

    private function formatForTenant(Extension $e, int $tenantId): array
    {
        $activation = $e->tenantExtensions->firstWhere('tenant_id', $tenantId);

        return [
            'id'             => $e->id,
            'slug'           => $e->slug,
            'name'           => $e->name,
            'tagline'        => $e->tagline,
            'description'    => $e->description,
            'category'       => $e->category,
            'category_label' => $e->category_label,
            'category_icon'  => $e->category_icon,
            'category_color' => $e->category_color,
            'icon'           => $e->icon_class,
            'icon_url'       => $e->icon_url,
            'icon_bg_color'  => $e->icon_bg_color,
            'pricing_type'   => $e->pricing_type,
            'pricing_label'  => $e->pricing_label,
            'price'          => $e->price,
            'currency'       => $e->currency,
            'is_free'        => $e->is_free,
            'has_trial'      => $e->has_trial,
            'trial_days'     => $e->trial_days,
            'status'         => $e->status,
            'is_featured'    => $e->is_featured,
            'is_new'         => $e->is_new,
            'is_official'    => $e->is_official,
            'is_verified'    => $e->is_verified,
            'installs_count' => $e->installs_count,
            'rating'         => $e->rating,
            'version'        => $e->version,
            // État du tenant
            'is_activated'   => $activation && in_array($activation->status, ['active','trial']),
            'activation_status' => $activation?->status,
            'is_trial'       => $activation?->status === 'trial',
            'trial_ends_at'  => $activation?->trial_ends_at?->format('d/m/Y'),
        ];
    }

    private function resolveExtension(string $slug): Extension
    {
        $this->service->ensureCatalogSeeded();

        return Extension::query()->where('slug', $slug)->firstOrFail();
    }
}
