<?php

namespace NexusExtensions\GoogleGmail;

use Illuminate\Support\ServiceProvider;
use NexusExtensions\GoogleGmail\Console\Commands\GoogleGmailSyncRealtimeCommand;
use NexusExtensions\GoogleGmail\Services\GoogleGmailService;
use NexusExtensions\GoogleGmail\Services\GoogleGmailSocketService;

class GoogleGmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/google-gmail.php', 'google-gmail');

        $this->app->bind(GoogleGmailSocketService::class, fn () => new GoogleGmailSocketService());
        $this->app->bind(GoogleGmailService::class, fn ($app) => new GoogleGmailService(
            $app->make(GoogleGmailSocketService::class)
        ));
        $this->app->bind('google-gmail.service', fn ($app) => $app->make(GoogleGmailService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'google-gmail');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'google-gmail');

        $this->publishes([
            __DIR__ . '/../config/google-gmail.php' => config_path('google-gmail.php'),
        ], 'google-gmail-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'google-gmail-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/google-gmail'),
        ], 'google-gmail-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/google-gmail'),
        ], 'google-gmail-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/google-gmail'),
        ], 'google-gmail-lang');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GoogleGmailSyncRealtimeCommand::class,
            ]);
        }
    }
}
