<?php

// Windows compatibility for packages expecting POSIX signal constants (e.g., Octane).
defined('SIGINT') || define('SIGINT', 2);
defined('SIGTERM') || define('SIGTERM', 15);
defined('SIGHUP') || define('SIGHUP', 1);

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

spl_autoload_register(function (string $class): void {
    $prefix = 'Vendor\\Stock\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $path = base_path('packages/vendor/stock/src/' . $relative . '.php');
    if (is_file($path)) {
        require_once $path;
    }
});

// Fallback autoload for Predis when composer autoload is stale.
spl_autoload_register(function (string $class): void {
    $prefix = 'Predis\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $path = base_path('vendor/predis/predis/src/' . $relative . '.php');
    if (is_file($path)) {
        require_once $path;
    }
});

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
