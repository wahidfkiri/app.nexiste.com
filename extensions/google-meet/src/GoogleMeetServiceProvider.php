<?php

namespace NexusExtensions\GoogleMeet;

use Illuminate\Support\ServiceProvider;
use NexusExtensions\GoogleMeet\Services\GoogleMeetService;

class GoogleMeetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/google-meet.php', 'google-meet');

        $this->app->bind(GoogleMeetService::class, fn () => new GoogleMeetService());
        $this->app->bind('google-meet.service', fn ($app) => $app->make(GoogleMeetService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'google-meet');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'google-meet');

        $this->publishes([
            __DIR__ . '/../config/google-meet.php' => config_path('google-meet.php'),
        ], 'google-meet-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'google-meet-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/google-meet'),
        ], 'google-meet-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/google-meet'),
        ], 'google-meet-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/google-meet'),
        ], 'google-meet-lang');
    }
}
