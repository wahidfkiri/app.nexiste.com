<?php

namespace Vendor\User;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Vendor\User\Services\UserService;
use Vendor\User\Repositories\UserRepository;
use Vendor\User\Http\Middleware\CanManageUsersMiddleware;
use Vendor\User\Http\Middleware\UserContextMiddleware;
use Vendor\Rbac\Services\TenantRoleService;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/user.php', 'user');

        // Bindings
        $this->app->bind(UserRepository::class, fn() => new UserRepository());

        $this->app->bind(UserService::class, function ($app) {
            return new UserService(
                $app->make(UserRepository::class),
                $app->make(TenantRoleService::class),
            );
        });

        $this->app->bind('user.service', fn($app) => $app->make(UserService::class));
    }

    public function boot(): void
    {
        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

        // Alias middleware
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware('can.manage.users', CanManageUsersMiddleware::class);
        $router->aliasMiddleware('user.context',     UserContextMiddleware::class);

        // Vues
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'user');

        // Traductions
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'user');

        // Publications config
        $this->publishes([
            __DIR__ . '/../config/user.php' => config_path('user.php'),
        ], 'user-config');

        // Publications migrations
        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'user-migrations');

        // Publications vues
        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/user'),
        ], 'user-views');

        // Publications assets
        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/user'),
        ], 'user-assets');

        // Publications traductions
        $this->publishes([
            __DIR__ . '/Resources/lang' => resource_path('lang/vendor/user'),
        ], 'user-lang');

        // Commandes Artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Vendor\User\Console\Commands\SeedTenantRoles::class,
            ]);
        }

        // Écoute de l'authentification pour tracker last_login
        $this->registerAuthListeners();
    }

    protected function registerAuthListeners(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function ($event) {
                $user = $event->user;
                if ($user && method_exists($user, 'update')) {
                    $user->update([
                        'last_login_at' => now(),
                        'last_login_ip' => request()->ip(),
                    ]);
                }
            }
        );
    }
}
