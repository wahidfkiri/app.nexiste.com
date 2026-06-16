<?php

namespace NexusExtensions\Chatbot;

use Illuminate\Support\ServiceProvider;
use NexusExtensions\Chatbot\Services\ChatbotService;
use NexusExtensions\Chatbot\Services\ChatbotSocketService;

class ChatbotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/chatbot.php', 'chatbot');

        $this->app->bind(ChatbotSocketService::class, fn () => new ChatbotSocketService());
        $this->app->bind(ChatbotService::class, fn ($app) => new ChatbotService($app->make(ChatbotSocketService::class)));
        $this->app->bind('chatbot.service', fn ($app) => $app->make(ChatbotService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'chatbot');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'chatbot');

        $this->publishes([
            __DIR__ . '/../config/chatbot.php' => config_path('chatbot.php'),
        ], 'chatbot-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'chatbot-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/chatbot'),
        ], 'chatbot-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/chatbot'),
        ], 'chatbot-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/chatbot'),
        ], 'chatbot-lang');
    }
}
