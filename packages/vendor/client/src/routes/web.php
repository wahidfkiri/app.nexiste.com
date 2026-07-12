<?php

use Illuminate\Support\Facades\Route;
use Vendor\Client\Http\Controllers\ClientController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:clients', 'tenant.permission:clients.read'])
    ->prefix('clients')
    ->name('clients.')
    ->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('/create', [ClientController::class, 'create'])->middleware('tenant.permission:clients.create')->name('create');
        Route::post('/', [ClientController::class, 'store'])->middleware('tenant.permission:clients.create')->name('store');

        Route::get('/data/table', [ClientController::class, 'getData'])->name('data');
        Route::get('/data/stats', [ClientController::class, 'getStats'])->name('stats');
        Route::get('/data/search', [ClientController::class, 'search'])->name('search');

        Route::post('/bulk/delete', [ClientController::class, 'bulkDelete'])->middleware('tenant.permission:clients.delete')->name('bulk.delete');
        Route::post('/bulk/status', [ClientController::class, 'bulkStatus'])->middleware('tenant.permission:clients.update')->name('bulk.status');

        Route::get('/export/csv', [ClientController::class, 'exportCsv'])->middleware('tenant.permission:clients.export')->name('export.csv');
        Route::get('/export/excel', [ClientController::class, 'exportExcel'])->middleware('tenant.permission:clients.export')->name('export.excel');
        Route::get('/export/pdf', [ClientController::class, 'exportPdf'])->middleware('tenant.permission:clients.export')->name('export.pdf');

        Route::post('/import', [ClientController::class, 'import'])->middleware('tenant.permission:clients.import')->name('import');
        Route::get('/import/template', [ClientController::class, 'downloadTemplate'])->middleware('tenant.permission:clients.import')->name('import.template');

        Route::get('/{client}', [ClientController::class, 'show'])->where('client', '[0-9a-fA-F-]+')->name('show');
        Route::get('/{client}/edit', [ClientController::class, 'edit'])->middleware('tenant.permission:clients.update')->where('client', '[0-9a-fA-F-]+')->name('edit');
        Route::put('/{client}', [ClientController::class, 'update'])->middleware('tenant.permission:clients.update')->where('client', '[0-9a-fA-F-]+')->name('update');
        Route::delete('/{client}', [ClientController::class, 'destroy'])->middleware('tenant.permission:clients.delete')->where('client', '[0-9a-fA-F-]+')->name('destroy');
    });
