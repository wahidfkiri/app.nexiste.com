<?php

namespace App\Providers;

use App\Notifications\DraftReminderNotification;
use App\Notifications\DraftSavedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use Illuminate\Notifications\DatabaseNotification;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Extensions\Services\ExtensionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (
            $this->app->environment(['local', 'development'])
            && class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)
        ) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (
            class_exists(ExtensionService::class)
            && Schema::hasTable('extensions')
        ) {
            app(ExtensionService::class)->ensureCatalogSeeded();
        }

        View::composer('layouts.global', function ($view): void {
            $apps = collect();
            $appsByCategory = collect();
            $notifications = collect();
            $notificationsUnreadCount = 0;
            $layoutAccess = [
                'dashboard' => false,
                'users' => false,
                'marketplace' => false,
                'settings' => false,
            ];

            if (
                Auth::check()
                && class_exists(TenantExtension::class)
                && Schema::hasTable('tenant_extensions')
                && Schema::hasTable('extensions')
            ) {
                $user = Auth::user();
                $tenantId = (int) (session('current_tenant_id') ?: ($user->tenant_id ?? 0));
                $canAccessAny = $this->tenantUserCanAccessAny($user, $tenantId);

                $layoutAccess = [
                    'dashboard' => $canAccessAny(['dashboard.read']),
                    'users' => $canAccessAny(['users.read']),
                    'marketplace' => $canAccessAny(['marketplace.read']),
                    'settings' => $canAccessAny(['settings.read']),
                ];

                $routeMap = [
                    'clients' => ['route' => 'clients.index', 'permission' => 'clients.read', 'icon' => 'fa-users', 'icon_bg_color' => '#2563eb'],
                    'stock' => ['route' => 'stock.articles.index', 'permission' => 'stock.read', 'icon' => 'fa-boxes-stacked', 'icon_bg_color' => '#0891b2'],
                    'invoice' => ['route' => 'invoices.index', 'permission' => 'invoices.read', 'icon' => 'fa-file-invoice', 'icon_bg_color' => '#7c3aed'],
                    'projects' => ['route' => 'projects.index', 'permission' => 'projects.view', 'icon' => 'fa-diagram-project', 'icon_bg_color' => '#0ea5e9'],
                    'notion-workspace' => ['route' => 'notion-workspace.index', 'permission' => 'notion.view', 'icon' => 'fa-book-open', 'icon_bg_color' => '#111827'],
                    'trello-integration' => ['route' => 'trello-integration.index', 'permission' => 'trello.view', 'icon' => 'fab fa-trello', 'icon_bg_color' => '#026aa7'],
                    'google-drive' => ['route' => 'google-drive.index', 'permission' => 'google-drive.view', 'icon' => 'fa-google-drive', 'icon_bg_color' => '#4285F4'],
                    'gdrive' => ['route' => 'google-drive.index', 'permission' => 'google-drive.view', 'icon' => 'fa-google-drive', 'icon_bg_color' => '#4285F4'],
                    'dropbox' => ['route' => 'dropbox.index', 'permission' => 'dropbox.view', 'icon' => 'fa-dropbox', 'icon_bg_color' => '#0061ff'],
                    'google-calendar' => ['route' => 'google-calendar.index', 'permission' => 'google-calendar.view', 'icon' => 'fa-calendar-days', 'icon_bg_color' => '#4285F4'],
                    'google-sheets' => ['route' => 'google-sheets.index', 'permission' => 'google-sheets.view', 'icon' => 'fa-file-excel', 'icon_bg_color' => '#0f9d58'],
                    'google-docx' => ['route' => 'google-docx.index', 'permission' => 'google-docx.view', 'icon' => 'fa-file-word', 'icon_bg_color' => '#1a73e8'],
                    'google-gmail' => ['route' => 'google-gmail.index', 'permission' => 'google-gmail.view', 'icon' => 'fa-envelope-open-text', 'icon_bg_color' => '#ea4335'],
                    'google-meet' => ['route' => 'google-meet.index', 'permission' => 'google-meet.view', 'icon' => 'fa-video', 'icon_bg_color' => '#34a853'],
                    'slack' => ['route' => 'slack.index', 'permission' => 'slack.view', 'icon' => 'fa-slack', 'icon_bg_color' => '#4A154B'],
                    'chatbot' => ['route' => 'chatbot.index', 'permission' => 'chatbot.view', 'icon' => 'fa-comments', 'icon_bg_color' => '#0ea5e9'],
                ];

                $normalizeFaClass = static function (?string $value, string $fallback = 'fa-puzzle-piece'): string {
                    $raw = trim((string) ($value ?? ''));
                    if ($raw === '') {
                        $raw = trim($fallback);
                    }

                    $raw = preg_replace('/\s+/', ' ', $raw) ?: '';
                    $hasGlyph = preg_match('/(^|\s)fa-[a-z0-9-]+(\s|$)/i', $raw) === 1;
                    $hasFamily = preg_match('/(^|\s)(fa|fas|far|fal|fad|fab|fat|fa-solid|fa-regular|fa-light|fa-thin|fa-brands)(\s|$)/i', $raw) === 1;

                    if (!$hasGlyph) {
                        return 'fas fa-puzzle-piece';
                    }

                    if (!$hasFamily) {
                        return 'fas ' . $raw;
                    }

                    return $raw;
                };

                $apps = TenantExtension::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('status', ['active', 'trial'])
                    ->whereHas('extension', fn ($query) => $query->where('status', 'active'))
                    ->with('extension')
                    ->get()
                    ->filter(fn ($activation) => $activation->extension !== null && (string) $activation->extension->status === 'active')
                    ->map(function ($activation) use ($routeMap, $normalizeFaClass, $canAccessAny) {
                        $extension = $activation->extension;
                        $slug = (string) $extension->slug;
                        $map = $routeMap[$slug] ?? null;
                        $targetRoute = $map['route'] ?? null;
                        $permissions = (array) ($map['permissions'] ?? ($map['permission'] ?? ['extensions.read']));
                        $url = null;

                        if ($targetRoute && Route::has($targetRoute)) {
                            $url = route($targetRoute);
                        } elseif (Route::has('marketplace.show')) {
                            $url = route('marketplace.show', $slug);
                            $permissions = ['marketplace.read'];
                        }

                        $icon = $normalizeFaClass((string) ($extension->icon_class ?? $extension->icon ?? ''), (string) ($map['icon'] ?? 'fa-puzzle-piece'));
                        $iconBgColor = (string) ($extension->icon_bg_color ?? ($map['icon_bg_color'] ?? '#334155'));
                        $categoryKey = (string) ($extension->category ?? 'other');

                        return (object) [
                            'slug' => $slug,
                            'name' => (string) $extension->name,
                            'icon' => $icon,
                            'icon_url' => (string) ($extension->icon_url ?? ''),
                            'icon_bg_color' => $iconBgColor,
                            'url' => $url,
                            'status' => (string) $activation->status,
                            'sort_order' => (int) ($extension->sort_order ?? 9999),
                            'category_key' => $categoryKey,
                            'category_label' => (string) ($extension->category_label ?? ucfirst($categoryKey)),
                            'category_icon' => $normalizeFaClass((string) ($extension->category_icon ?? ''), 'fa-puzzle-piece'),
                            'category_color' => (string) ($extension->category_color ?? '#64748b'),
                            'can_access' => $canAccessAny($permissions),
                        ];
                    })
                    ->filter(fn ($app) => !empty($app->url) && (bool) ($app->can_access ?? false))
                    ->sortBy(fn ($app) => sprintf('%05d-%s', (int) ($app->sort_order ?? 9999), mb_strtolower((string) $app->name)))
                    ->values();

                $categoryOrder = array_keys((array) config('extensions.categories', []));
                $orderMap = array_flip($categoryOrder);

                $appsByCategory = $apps
                    ->groupBy(fn ($app) => (string) ($app->category_key ?? 'other'))
                    ->map(function ($group, $categoryKey) {
                        $first = $group->first();
                        return (object) [
                            'key' => (string) $categoryKey,
                            'label' => (string) ($first->category_label ?? ucfirst((string) $categoryKey)),
                            'icon' => (string) ($first->category_icon ?? 'fa-puzzle-piece'),
                            'color' => (string) ($first->category_color ?? '#64748b'),
                            'apps' => $group->values(),
                        ];
                    })
                    ->sortBy(fn ($cat) => ($orderMap[$cat->key] ?? 9999) . '-' . mb_strtolower((string) $cat->label))
                    ->values();
            }

            if (Auth::check() && Schema::hasTable('notifications')) {
                $user = Auth::user();

                $notificationsUnreadCount = (int) $user->unreadNotifications()->count();
                $notifications = $user->notifications()
                    ->latest('updated_at')
                    ->limit(8)
                    ->get()
                    ->map(function (DatabaseNotification $notification) {
                        $data = is_array($notification->data) ? $notification->data : [];
                        $resolved = $this->resolveNotificationMeta($notification, $data);

                        return (object) [
                            'id' => (string) $notification->id,
                            'title' => (string) ($data['title'] ?? 'Notification CRM'),
                            'message' => (string) ($data['message'] ?? 'Une nouvelle notification est disponible.'),
                            'action_url' => !empty($data['action_url'])
                                ? route('notifications.open', ['notification' => $notification->getKey()])
                                : '',
                            'created_at' => $notification->created_at,
                            'read_at' => $notification->read_at,
                            'is_unread' => $notification->read_at === null,
                            'icon' => $resolved['icon'],
                            'accent' => $resolved['accent'],
                        ];
                    })
                    ->values();
            }

            $view->with('layoutInstalledApps', $apps);
            $view->with('layoutInstalledAppsByCategory', $appsByCategory);
            $view->with('layoutInstalledAppsCount', $apps->count());
            $view->with('layoutNotifications', $notifications);
            $view->with('layoutNotificationsUnreadCount', $notificationsUnreadCount);
            $view->with('layoutAccess', $layoutAccess);
        });

        View::composer([
            'google-calendar::calendar.index',
            'google-docx::docs.index',
            'google-drive::drive.index',
            'google-gmail::gmail.index',
            'google-meet::meet.index',
            'google-sheets::sheets.index',
            'dropbox::drive.index',
            'slack::slack.index',
            'chatbot::chatbot.index',
            'notion-workspace::notion.index',
            'trello-integration::trello.index',
            'projects::projects.index',
        ], function ($view): void {
            $view->with('currentExtensionMeta', $this->resolveCurrentExtensionMeta());
        });
    }

    private function tenantUserCanAccessAny($user, int $tenantId): \Closure
    {
        return function (array|string $permissions) use ($user, $tenantId): bool {
            $permissions = is_array($permissions) ? $permissions : [$permissions];
            $permissions = array_values(array_filter(array_map('trim', $permissions)));

            if (!$user || $permissions === []) {
                return false;
            }

            try {
                $isSuperAdmin = false;
                if (method_exists($user, 'hasAnyRole')) {
                    $isSuperAdmin = $user->hasAnyRole(['super_admin', 'super-admin']);
                } elseif (method_exists($user, 'hasRole')) {
                    $isSuperAdmin = $user->hasRole('super_admin') || $user->hasRole('super-admin');
                }

                if ($isSuperAdmin) {
                    return true;
                }

                if (method_exists($user, 'hasTenantRole') && $user->hasTenantRole(['owner', 'admin'], $tenantId)) {
                    return true;
                }

                $rbacReady = Schema::hasTable('permissions')
                    && Schema::hasTable('roles')
                    && Schema::hasTable('role_has_permissions');

                if (!$rbacReady || !method_exists($user, 'hasTenantPermission')) {
                    return true;
                }

                foreach ($permissions as $permission) {
                    if ($user->hasTenantPermission($permission, $tenantId)) {
                        return true;
                    }
                }
            } catch (\Throwable) {
                return true;
            }

            return false;
        };
    }

    private function resolveCurrentExtensionMeta(): ?object
    {
        if (!class_exists(Extension::class) || !Schema::hasTable('extensions')) {
            return null;
        }

        $slug = $this->resolveExtensionSlugFromRoute(Route::currentRouteName());
        if ($slug === null) {
            return null;
        }

        static $cache = [];

        if (array_key_exists($slug, $cache)) {
            return $cache[$slug];
        }

        $extension = Extension::query()
            ->where('slug', $slug)
            ->first();

        if (!$extension) {
            $cache[$slug] = null;
            return null;
        }

        $cache[$slug] = (object) [
            'slug' => (string) $extension->slug,
            'name' => (string) $extension->name,
            'icon' => (string) ($extension->icon_url ?: $extension->icon_class ?: $extension->icon ?: 'fas fa-puzzle-piece'),
            'icon_url' => (string) ($extension->icon_url ?? ''),
            'icon_bg_color' => (string) ($extension->icon_bg_color ?? ''),
        ];

        return $cache[$slug];
    }

    private function resolveExtensionSlugFromRoute(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        $map = [
            'google-calendar.*' => 'google-calendar',
            'google-docx.*' => 'google-docx',
            'google-drive.*' => 'google-drive',
            'google-gmail.*' => 'google-gmail',
            'google-meet.*' => 'google-meet',
            'google-sheets.*' => 'google-sheets',
            'dropbox.*' => 'dropbox',
            'slack.*' => 'slack',
            'chatbot.*' => 'chatbot',
            'notion-workspace.*' => 'notion-workspace',
            'trello-integration.*' => 'trello-integration',
            'projects.*' => 'projects',
        ];

        foreach ($map as $pattern => $slug) {
            if (Str::is($pattern, $routeName)) {
                return $slug;
            }
        }

        return null;
    }

    private function resolveNotificationMeta(DatabaseNotification $notification, array $data): array
    {
        $type = (string) $notification->type;
        $kind = (string) ($data['notification_kind'] ?? '');

        if (
            $kind === 'draft_saved'
            || $kind === 'draft_reminder'
            || in_array($type, [DraftSavedNotification::class, DraftReminderNotification::class], true)
        ) {
            return [
                'icon' => 'fa-pen-to-square',
                'accent' => $kind === 'draft_reminder' ? '#dc2626' : '#f59e0b',
            ];
        }

        if ($kind === 'automation_suggestion_pending') {
            $providerSlug = (string) ($data['provider_slug'] ?? '');

            return match ($providerSlug) {
                'google-gmail' => ['icon' => 'fa-envelope', 'accent' => '#ea4335'],
                'google-calendar' => ['icon' => 'fa-calendar-days', 'accent' => '#0f9d58'],
                'google-drive' => ['icon' => 'fa-folder-open', 'accent' => '#1a73e8'],
                'dropbox' => ['icon' => 'fa-dropbox', 'accent' => '#0061ff'],
                'google-meet' => ['icon' => 'fa-video', 'accent' => '#34a853'],
                'google-sheets' => ['icon' => 'fa-table-cells', 'accent' => '#188038'],
                'google-docx' => ['icon' => 'fa-file-word', 'accent' => '#2563eb'],
                'slack' => ['icon' => 'fa-slack', 'accent' => '#7c3aed'],
                default => ['icon' => 'fa-wand-magic-sparkles', 'accent' => '#2563eb'],
            };
        }

        return [
            'icon' => 'fa-bell',
            'accent' => '#2563eb',
        ];
    }
}
