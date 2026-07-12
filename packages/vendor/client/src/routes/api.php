<?php

use Illuminate\Support\Facades\Route;
use Vendor\Client\Http\Controllers\Api\ClientApiController;

Route::middleware(['api', 'auth:sanctum', 'tenant', 'extension.active:clients'])
    ->prefix('api/clients')
    ->name('api.clients.')
    ->group(function () {
        Route::get('/', [ClientApiController::class, 'index'])->middleware('tenant.permission:clients.read')->name('index');
        Route::post('/', [ClientApiController::class, 'store'])->middleware('tenant.permission:clients.create')->name('store');
        Route::get('/export/all', [ClientApiController::class, 'export'])->middleware('tenant.permission:clients.export')->name('export');
        Route::post('/import', [ClientApiController::class, 'import'])->middleware('tenant.permission:clients.import')->name('import');
        Route::post('/bulk-delete', [ClientApiController::class, 'bulkDelete'])->middleware('tenant.permission:clients.delete')->name('bulk-delete');
        Route::post('/bulk-status', [ClientApiController::class, 'bulkStatus'])->middleware('tenant.permission:clients.update')->name('bulk-status');
        Route::get('/search', [ClientApiController::class, 'search'])->middleware('tenant.permission:clients.read')->name('search');
        Route::get('/filter', [ClientApiController::class, 'filter'])->middleware('tenant.permission:clients.read')->name('filter');
        Route::get('/stats/summary', [ClientApiController::class, 'getStats'])->middleware('tenant.permission:clients.read')->name('stats');
        Route::get('/{client}', [ClientApiController::class, 'show'])->middleware('tenant.permission:clients.read')->where('client', '[0-9a-fA-F-]+')->name('show');
        Route::put('/{client}', [ClientApiController::class, 'update'])->middleware('tenant.permission:clients.update')->where('client', '[0-9a-fA-F-]+')->name('update');
        Route::delete('/{client}', [ClientApiController::class, 'destroy'])->middleware('tenant.permission:clients.delete')->where('client', '[0-9a-fA-F-]+')->name('destroy');
    });
