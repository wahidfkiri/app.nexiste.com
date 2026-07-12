<?php

use Illuminate\Support\Facades\Route;
use Vendor\Invoice\Http\Controllers\Api\InvoiceApiController;

Route::middleware(['api', 'auth:sanctum', 'tenant', 'extension.active:invoice'])
    ->prefix('api/v1')
    ->name('api.')
    ->group(function () {
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceApiController::class, 'index'])->middleware('tenant.permission:invoices.read')->name('index');
            Route::post('/', [InvoiceApiController::class, 'store'])->middleware('tenant.permission:invoices.create')->name('store');
            Route::get('/stats', [InvoiceApiController::class, 'stats'])->middleware('tenant.permission:invoices.read')->name('stats');
            Route::get('/{invoice}', [InvoiceApiController::class, 'show'])->middleware('tenant.permission:invoices.read')->where('invoice', '[0-9a-fA-F-]+')->name('show');
            Route::put('/{invoice}', [InvoiceApiController::class, 'update'])->middleware('tenant.permission:invoices.update')->where('invoice', '[0-9a-fA-F-]+')->name('update');
            Route::delete('/{invoice}', [InvoiceApiController::class, 'destroy'])->middleware('tenant.permission:invoices.delete')->where('invoice', '[0-9a-fA-F-]+')->name('destroy');
            Route::post('/{invoice}/send', [InvoiceApiController::class, 'send'])->middleware('tenant.permission:invoices.send')->where('invoice', '[0-9a-fA-F-]+')->name('send');
            Route::post('/{invoice}/payments', [InvoiceApiController::class, 'addPayment'])->middleware('tenant.permission:payments.create')->where('invoice', '[0-9a-fA-F-]+')->name('payments.store');
        });

        Route::prefix('quotes')->name('quotes.')->group(function () {
            Route::get('/', [InvoiceApiController::class, 'quotesIndex'])->middleware('tenant.permission:quotes.read')->name('index');
            Route::post('/', [InvoiceApiController::class, 'quotesStore'])->middleware('tenant.permission:quotes.create')->name('store');
            Route::get('/{quote}', [InvoiceApiController::class, 'quotesShow'])->middleware('tenant.permission:quotes.read')->where('quote', '[0-9a-fA-F-]+')->name('show');
            Route::put('/{quote}', [InvoiceApiController::class, 'quotesUpdate'])->middleware('tenant.permission:quotes.update')->where('quote', '[0-9a-fA-F-]+')->name('update');
            Route::delete('/{quote}', [InvoiceApiController::class, 'quotesDestroy'])->middleware('tenant.permission:quotes.delete')->where('quote', '[0-9a-fA-F-]+')->name('destroy');
            Route::post('/{quote}/convert', [InvoiceApiController::class, 'quotesConvert'])->middleware('tenant.permission:quotes.convert')->where('quote', '[0-9a-fA-F-]+')->name('convert');
        });
    });
