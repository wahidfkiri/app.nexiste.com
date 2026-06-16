<?php

use Illuminate\Support\Facades\Route;
use Vendor\Extensions\Http\Controllers\MarketplaceController;
use Vendor\Extensions\Http\Controllers\SuperAdmin\ExtensionAdminController;

Route::middleware(['web', 'auth', 'superadmin'])
    ->prefix('superadmin/extensions')
    ->name('superadmin.extensions.')
    ->group(function () {
        Route::get('/', [ExtensionAdminController::class, 'index'])->name('index');
        Route::get('/create', [ExtensionAdminController::class, 'create'])->name('create');
        Route::post('/', [ExtensionAdminController::class, 'store'])->name('store');
        Route::get('/data/table', [ExtensionAdminController::class, 'getData'])->name('data');
        Route::get('/data/stats', [ExtensionAdminController::class, 'getStats'])->name('stats');
        Route::get('/export/excel', [ExtensionAdminController::class, 'exportExcel'])->name('export.excel');

        Route::prefix('activations')->name('activations.')->group(function () {
            Route::get('/', [ExtensionAdminController::class, 'activationsIndex'])->name('index');
            Route::get('/data', [ExtensionAdminController::class, 'activationsData'])->name('data');
            Route::post('/{activation}/suspend', [ExtensionAdminController::class, 'suspendActivation'])->name('suspend');
            Route::post('/{activation}/restore', [ExtensionAdminController::class, 'restoreActivation'])->name('restore');
        });

        Route::get('/{extension}', [ExtensionAdminController::class, 'show'])->name('show');
        Route::get('/{extension}/edit', [ExtensionAdminController::class, 'edit'])->name('edit');
        Route::put('/{extension}', [ExtensionAdminController::class, 'update'])->name('update');
        Route::delete('/{extension}', [ExtensionAdminController::class, 'destroy'])->name('destroy');
        Route::post('/{extension}/featured', [ExtensionAdminController::class, 'toggleFeatured'])->name('featured');
        Route::post('/{extension}/status', [ExtensionAdminController::class, 'toggleStatus'])->name('status');
    });

Route::middleware(['web', 'auth', 'tenant', 'tenant.permission:marketplace.read'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])->name('index');
        Route::get('/my-apps', [MarketplaceController::class, 'myApps'])->middleware('tenant.permission:extensions.read')->name('my-apps');
        Route::get('/data/apps', [MarketplaceController::class, 'getData'])->name('data');
        Route::get('/data/stats', [MarketplaceController::class, 'getStats'])->name('stats');
        Route::get('/{slug}', [MarketplaceController::class, 'show'])->name('show');
        Route::get('/{slug}/settings', [MarketplaceController::class, 'settings'])->middleware('tenant.permission:extensions.settings')->name('settings');
        Route::post('/{slug}/activate', [MarketplaceController::class, 'activate'])->middleware('tenant.permission:extensions.manage')->name('activate');
        Route::post('/{slug}/deactivate', [MarketplaceController::class, 'deactivate'])->middleware('tenant.permission:extensions.manage')->name('deactivate');
        Route::post('/{slug}/settings/save', [MarketplaceController::class, 'saveSettings'])->middleware('tenant.permission:extensions.settings')->name('settings.save');
    });
