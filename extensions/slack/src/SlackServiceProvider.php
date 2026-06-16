<?php

namespace NexusExtensions\Slack;

use Illuminate\Support\ServiceProvider;
use NexusExtensions\Slack\Services\SlackService;
use NexusExtensions\Slack\Services\SlackSocketService;

class SlackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/slack.php', 'slack');

        $this->app->bind(SlackSocketService::class, fn () => new SlackSocketService());
        $this->app->bind(SlackService::class, fn ($app) => new SlackService($app->make(SlackSocketService::class)));
        $this->app->bind('slack.service', fn ($app) => $app->make(SlackService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'slack');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'slack');

        $this->publishes([
            __DIR__ . '/../config/slack.php' => config_path('slack.php'),
        ], 'slack-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'slack-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/slack'),
        ], 'slack-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/slack'),
        ], 'slack-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/slack'),
        ], 'slack-lang');
    }
}
