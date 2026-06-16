<?php

namespace Vendor\GoogleCalendar;

use Illuminate\Support\ServiceProvider;
use Vendor\GoogleCalendar\Services\GoogleCalendarService;

class GoogleCalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/google-calendar.php', 'google-calendar');

        $this->app->bind(GoogleCalendarService::class, fn () => new GoogleCalendarService());
        $this->app->bind('google-calendar.service', fn ($app) => $app->make(GoogleCalendarService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'google-calendar');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'google-calendar');

        $this->publishes([
            __DIR__ . '/../config/google-calendar.php' => config_path('google-calendar.php'),
        ], 'google-calendar-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'google-calendar-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/google-calendar'),
        ], 'google-calendar-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/google-calendar'),
        ], 'google-calendar-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/google-calendar'),
        ], 'google-calendar-lang');
    }
}
