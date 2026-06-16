<?php

namespace Vendor\Automation;

use Illuminate\Support\ServiceProvider;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\Automation\Events\AutomationEventFailed;
use Vendor\Automation\Events\AutomationEventQueued;
use Vendor\Automation\Listeners\CaptureAutomationSuggestions;
use Vendor\Automation\Listeners\QueueAutomationExecution;
use Vendor\Automation\Listeners\SyncReconnectNotificationOnFailure;
use Vendor\Automation\Registries\ActionRegistry;
use Vendor\Automation\Registries\SuggestionRegistry;
use Vendor\Automation\Services\AutomationPreferenceService;
use Vendor\Automation\Services\AutomationEngine;
use Vendor\Automation\Services\AutomationExecutor;
use Vendor\Automation\Services\ExtensionAvailabilityService;
use Vendor\Automation\Services\AutomationSuggestionPresenter;

class AutomationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/automation.php', 'automation');

        $this->app->singleton(SuggestionRegistry::class, function ($app) {
            return new SuggestionRegistry($app, (array) config('automation.providers', []));
        });

        $this->app->singleton(ActionRegistry::class, function ($app) {
            return new ActionRegistry($app, (array) config('automation.actions', []));
        });

        $this->app->singleton(ExtensionAvailabilityService::class, function ($app) {
            return new ExtensionAvailabilityService();
        });

        $this->app->singleton(AutomationPreferenceService::class, function ($app) {
            return new AutomationPreferenceService();
        });

        $this->app->singleton(AutomationSuggestionPresenter::class, function ($app) {
            return new AutomationSuggestionPresenter(
                $app->make(AutomationPreferenceService::class)
            );
        });

        $this->app->singleton(AutomationEngine::class, function ($app) {
            return new AutomationEngine(
                $app->make(SuggestionRegistry::class),
                $app->make(ActionRegistry::class),
                $app->make(AutomationPreferenceService::class)
            );
        });

        $this->app->singleton(AutomationExecutor::class, function ($app) {
            return new AutomationExecutor($app->make(ActionRegistry::class));
        });

        $this->app->alias(AutomationEngine::class, 'automation.engine');
        $this->app->alias(AutomationExecutor::class, 'automation.executor');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'automation');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        $this->publishes([
            __DIR__ . '/../config/automation.php' => config_path('automation.php'),
        ], 'automation-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'automation-migrations');

        $this->registerEvents();
    }

    protected function registerEvents(): void
    {
        $this->app['events']->listen(
            AutomationEventQueued::class,
            QueueAutomationExecution::class
        );

        $this->app['events']->listen(
            AutomationEventFailed::class,
            SyncReconnectNotificationOnFailure::class
        );

        $this->app['events']->listen(
            AutomationContextEvent::class,
            CaptureAutomationSuggestions::class
        );
    }
}
