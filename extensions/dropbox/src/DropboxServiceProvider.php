<?php

namespace NexusExtensions\Dropbox;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use NexusExtensions\Dropbox\Services\DropboxService;

class DropboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dropbox.php', 'dropbox');

        $this->app->bind(DropboxService::class, fn () => new DropboxService());
        $this->app->bind('dropbox.service', fn ($app) => $app->make(DropboxService::class));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'dropbox');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'dropbox');
        $this->syncAssetsToPublic();

        $this->publishes([
            __DIR__ . '/../config/dropbox.php' => config_path('dropbox.php'),
        ], 'dropbox-config');

        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'dropbox-migrations');

        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/dropbox'),
        ], 'dropbox-views');

        $this->publishes([
            __DIR__ . '/Resources/assets' => public_path('vendor/dropbox'),
        ], 'dropbox-assets');

        $this->publishes([
            __DIR__ . '/Resources/lang' => lang_path('vendor/dropbox'),
        ], 'dropbox-lang');
    }

    private function syncAssetsToPublic(): void
    {
        $sourceRoot = __DIR__ . '/Resources/assets';
        $targetRoot = public_path('vendor/dropbox');

        if (!File::isDirectory($sourceRoot)) {
            return;
        }

        foreach (File::allFiles($sourceRoot) as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $targetPath = $targetRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $targetDir = dirname($targetPath);

            if (!File::isDirectory($targetDir)) {
                File::ensureDirectoryExists($targetDir);
            }

            if (!File::exists($targetPath) || $file->getMTime() > File::lastModified($targetPath)) {
                File::copy($file->getPathname(), $targetPath);
            }
        }
    }
}



