<?php

namespace Vendor\Automation\Services;

use Illuminate\Support\Facades\Route;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class ExtensionAvailabilityService
{
    protected array $extensionIdCache = [];

    protected array $defaultRoutes = [
        'google-gmail' => 'google-gmail.index',
        'google-calendar' => 'google-calendar.index',
        'google-drive' => 'google-drive.index',
        'dropbox' => 'dropbox.index',
        'google-meet' => 'google-meet.index',
        'google-sheets' => 'google-sheets.index',
        'google-docx' => 'google-docx.index',
        'notion-workspace' => 'notion-workspace.index',
        'chatbot' => 'chatbot.index',
        'slack' => 'slack.index',
        'invoice' => 'invoices.index',
        'projects' => 'projects.index',
    ];

    public function isActive(int $tenantId, string $slug): bool
    {
        $extensionId = $this->extensionId($slug);
        if (!$extensionId) {
            return false;
        }

        return TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', $extensionId)
            ->whereIn('status', ['active', 'trial'])
            ->exists();
    }

    protected array $globalAvailabilityCache = [];

    public function isAvailableGlobally(string $slug): bool
    {
        if (array_key_exists($slug, $this->globalAvailabilityCache)) {
            return $this->globalAvailabilityCache[$slug];
        }

        $extensionId = $this->extensionId($slug);
        if (!$extensionId) {
            $this->globalAvailabilityCache[$slug] = false;
            return false;
        }

        $isActive = Extension::query()->where('id', $extensionId)->where('status', 'active')->exists();
        $this->globalAvailabilityCache[$slug] = $isActive;

        return $isActive;
    }

    public function preferredInstalled(int $tenantId, array $slugs): ?string
    {
        foreach ($slugs as $slug) {
            if ($this->isActive($tenantId, (string) $slug)) {
                return (string) $slug;
            }
        }

        return null;
    }

    public function targetUrl(string $slug, ?string $installedRoute = null): string
    {
        $routeName = $installedRoute ?: ($this->defaultRoutes[$slug] ?? null);
        if ($routeName && Route::has($routeName)) {
            return route($routeName);
        }

        if (Route::has('marketplace.show')) {
            return route('marketplace.show', $slug);
        }

        return Route::has('applications') ? route('applications') : url('/');
    }

    protected function extensionId(string $slug): ?int
    {
        if (array_key_exists($slug, $this->extensionIdCache)) {
            return $this->extensionIdCache[$slug];
        }

        $id = Extension::query()->where('slug', $slug)->value('id');
        $this->extensionIdCache[$slug] = $id ? (int) $id : null;

        return $this->extensionIdCache[$slug];
    }
}
