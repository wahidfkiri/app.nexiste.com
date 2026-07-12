<?php

use Illuminate\Support\Facades\Route;
use Vendor\Invoice\Http\Controllers\InvoiceController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:invoice'])->group(function () {
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->middleware('tenant.permission:invoices.read')->name('index');
        Route::get('/create', [InvoiceController::class, 'create'])->middleware('tenant.permission:invoices.create')->name('create');
        Route::post('/', [InvoiceController::class, 'store'])->middleware('tenant.permission:invoices.create')->name('store');

        Route::get('/data/table', [InvoiceController::class, 'getData'])->middleware('tenant.permission:invoices.read')->name('data');
        Route::get('/data/stats', [InvoiceController::class, 'getStats'])->middleware('tenant.permission:invoices.read')->name('stats');
        Route::post('/bulk/delete', [InvoiceController::class, 'bulkDelete'])->middleware('tenant.permission:invoices.delete')->name('bulk.delete');
        Route::post('/bulk/send', [InvoiceController::class, 'bulkSend'])->middleware('tenant.permission:invoices.send')->name('bulk.send');

        Route::get('/export/csv', [InvoiceController::class, 'exportCsv'])->middleware('tenant.permission:invoices.export')->name('export.csv');
        Route::get('/export/excel', [InvoiceController::class, 'exportExcel'])->middleware('tenant.permission:invoices.export')->name('export.excel');
        Route::get('/export/pdf', [InvoiceController::class, 'exportPdf'])->middleware('tenant.permission:invoices.export')->name('export.pdf');
        Route::post('/import', [InvoiceController::class, 'import'])->middleware('tenant.permission:invoices.import')->name('import');

        Route::prefix('quotes')->name('quotes.')->group(function () {
            Route::get('/', [InvoiceController::class, 'quotesIndex'])->middleware('tenant.permission:quotes.read')->name('index');
            Route::get('/create', [InvoiceController::class, 'quotesCreate'])->middleware('tenant.permission:quotes.create')->name('create');
            Route::post('/', [InvoiceController::class, 'quotesStore'])->middleware('tenant.permission:quotes.create')->name('store');
            Route::get('/data/table', [InvoiceController::class, 'quotesGetData'])->middleware('tenant.permission:quotes.read')->name('data');
            Route::get('/export/csv', [InvoiceController::class, 'quotesExportCsv'])->middleware('tenant.permission:quotes.export')->name('export.csv');
            Route::get('/export/excel', [InvoiceController::class, 'quotesExportExcel'])->middleware('tenant.permission:quotes.export')->name('export.excel');
            Route::get('/{quote}', [InvoiceController::class, 'quotesShow'])->middleware('tenant.permission:quotes.read')->where('quote', '[0-9a-fA-F-]+')->name('show');
            Route::get('/{quote}/edit', [InvoiceController::class, 'quotesEdit'])->middleware('tenant.permission:quotes.update')->where('quote', '[0-9a-fA-F-]+')->name('edit');
            Route::put('/{quote}', [InvoiceController::class, 'quotesUpdate'])->middleware('tenant.permission:quotes.update')->where('quote', '[0-9a-fA-F-]+')->name('update');
            Route::delete('/{quote}', [InvoiceController::class, 'quotesDestroy'])->middleware('tenant.permission:quotes.delete')->where('quote', '[0-9a-fA-F-]+')->name('destroy');
            Route::post('/{quote}/convert', [InvoiceController::class, 'quotesConvert'])->middleware('tenant.permission:quotes.convert')->where('quote', '[0-9a-fA-F-]+')->name('convert');
            Route::get('/{quote}/pdf', [InvoiceController::class, 'quotesDownloadPdf'])->middleware('tenant.permission:quotes.export')->where('quote', '[0-9a-fA-F-]+')->name('pdf');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [InvoiceController::class, 'paymentsIndex'])->middleware('tenant.permission:payments.read')->name('index');
            Route::get('/data/table', [InvoiceController::class, 'paymentsData'])->middleware('tenant.permission:payments.read')->name('data');
            Route::get('/data/stats', [InvoiceController::class, 'paymentsStats'])->middleware('tenant.permission:payments.read')->name('stats');
            Route::get('/export/csv', [InvoiceController::class, 'paymentsExportCsv'])->middleware('tenant.permission:payments.export')->name('export.csv');
            Route::get('/export/excel', [InvoiceController::class, 'paymentsExportExcel'])->middleware('tenant.permission:payments.export')->name('export.excel');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [InvoiceController::class, 'reportsIndex'])->middleware('tenant.permission:reports.read')->name('index');
            Route::get('/export/{format}', [InvoiceController::class, 'reportsExport'])->middleware('tenant.permission:reports.export')->name('export');
        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [InvoiceController::class, 'settingsIndex'])->middleware('tenant.permission:settings.read')->name('index');
            Route::put('/', [InvoiceController::class, 'settingsUpdate'])->middleware('tenant.permission:settings.update')->name('update');
        });

        Route::get('/currencies/rate', [InvoiceController::class, 'getExchangeRate'])->middleware('tenant.permission:invoices.read,quotes.read')->name('currencies.rate');

        Route::get('/{invoice}', [InvoiceController::class, 'show'])->middleware('tenant.permission:invoices.read')->where('invoice', '[0-9a-fA-F-]+')->name('show');
        Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->middleware('tenant.permission:invoices.update')->where('invoice', '[0-9a-fA-F-]+')->name('edit');
        Route::put('/{invoice}', [InvoiceController::class, 'update'])->middleware('tenant.permission:invoices.update')->where('invoice', '[0-9a-fA-F-]+')->name('update');
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->middleware('tenant.permission:invoices.delete')->where('invoice', '[0-9a-fA-F-]+')->name('destroy');
        Route::post('/{invoice}/send', [InvoiceController::class, 'send'])->middleware('tenant.permission:invoices.send')->where('invoice', '[0-9a-fA-F-]+')->name('send');
        Route::post('/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->middleware('tenant.permission:invoices.create')->where('invoice', '[0-9a-fA-F-]+')->name('duplicate');
        Route::post('/{invoice}/payments', [InvoiceController::class, 'addPayment'])->middleware('tenant.permission:payments.create')->where('invoice', '[0-9a-fA-F-]+')->name('payments.store');
        Route::delete('/payments/{payment}', [InvoiceController::class, 'deletePayment'])->middleware('tenant.permission:payments.delete')->where('payment', '[0-9a-fA-F-]+')->name('payments.destroy');
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->middleware('tenant.permission:invoices.export')->where('invoice', '[0-9a-fA-F-]+')->name('pdf');
    });
});
