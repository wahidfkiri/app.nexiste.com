<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Vendor\Extensions\Services\ExtensionService;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class EnsureMarketplaceExtensionActive
{
    public function __construct(protected ExtensionService $extensions)
    {
    }

    public function handle(Request $request, Closure $next, string $slug)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (!Schema::hasTable('extensions') || !Schema::hasTable('tenant_extensions')) {
            return $next($request);
        }

        $this->extensions->ensureCatalogSeeded();

        $extension = Extension::query()->where('slug', $slug)->first();
        if (!$extension) {
            return $next($request);
        }

        $isActive = TenantExtension::query()
            ->where('tenant_id', (int) $user->tenant_id)
            ->where('extension_id', (int) $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();

        if ($isActive) {
            return $next($request);
        }

        $message = "Le module {$extension->name} n'est pas active pour votre entreprise. Activez-le depuis Marketplace.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'slug' => $slug,
            ], 403);
        }

        if (Route::has('marketplace.show')) {
            return redirect()->route('marketplace.show', $slug)->with('error', $message);
        }

        if (Route::has('marketplace.index')) {
            return redirect()->route('marketplace.index')->with('error', $message);
        }

        return redirect('/dashboard')->with('error', $message);
    }
}

