<?php

use Illuminate\Support\Facades\Route;
use Vendor\Stock\Http\Controllers\DeliveryNoteController;
use Vendor\Stock\Http\Controllers\StockController;
use Vendor\Stock\Http\Controllers\StockMovementController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:stock'])
    ->prefix('stock')
    ->name('stock.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('stock.articles.index'))->middleware('tenant.permission:stock.read');
        Route::get('/data/stats', [StockController::class, 'stats'])->middleware('tenant.permission:stock.read')->name('stats');

        Route::prefix('articles')->name('articles.')->group(function () {
            Route::get('/', [StockController::class, 'articlesIndex'])->middleware('tenant.permission:stock.read')->name('index');
            Route::get('/create', [StockController::class, 'articlesCreate'])->middleware('tenant.permission:stock.create')->name('create');
            Route::post('/', [StockController::class, 'articlesStore'])->middleware('tenant.permission:stock.create')->name('store');
            Route::get('/data/table', [StockController::class, 'articlesData'])->middleware('tenant.permission:stock.read')->name('data');
            Route::get('/data/search', [StockController::class, 'articlesSearch'])->middleware('tenant.permission:stock.read')->name('search');
            Route::get('/export/excel', [StockController::class, 'articlesExportExcel'])->middleware('tenant.permission:stock.export')->name('export.excel');
            Route::post('/import', [StockController::class, 'articlesImport'])->middleware('tenant.permission:stock.import')->name('import');
            Route::get('/{article}', [StockController::class, 'articlesShow'])->middleware('tenant.permission:stock.read')->where('article', '[0-9a-fA-F-]+')->name('show');
            Route::get('/{article}/edit', [StockController::class, 'articlesEdit'])->middleware('tenant.permission:stock.update')->where('article', '[0-9a-fA-F-]+')->name('edit');
            Route::put('/{article}', [StockController::class, 'articlesUpdate'])->middleware('tenant.permission:stock.update')->where('article', '[0-9a-fA-F-]+')->name('update');
            Route::delete('/{article}', [StockController::class, 'articlesDestroy'])->middleware('tenant.permission:stock.delete')->where('article', '[0-9a-fA-F-]+')->name('destroy');
        });

        Route::prefix('suppliers')->name('suppliers.')->group(function () {
            Route::get('/', [StockController::class, 'suppliersIndex'])->middleware('tenant.permission:suppliers.read')->name('index');
            Route::get('/create', [StockController::class, 'suppliersCreate'])->middleware('tenant.permission:suppliers.create')->name('create');
            Route::post('/', [StockController::class, 'suppliersStore'])->middleware('tenant.permission:suppliers.create')->name('store');
            Route::get('/data/table', [StockController::class, 'suppliersData'])->middleware('tenant.permission:suppliers.read')->name('data');
            Route::get('/export/excel', [StockController::class, 'suppliersExportExcel'])->middleware('tenant.permission:suppliers.export')->name('export.excel');
            Route::get('/{supplier}', [StockController::class, 'suppliersShow'])->middleware('tenant.permission:suppliers.read')->where('supplier', '[0-9a-fA-F-]+')->name('show');
            Route::get('/{supplier}/edit', [StockController::class, 'suppliersEdit'])->middleware('tenant.permission:suppliers.update')->where('supplier', '[0-9a-fA-F-]+')->name('edit');
            Route::put('/{supplier}', [StockController::class, 'suppliersUpdate'])->middleware('tenant.permission:suppliers.update')->where('supplier', '[0-9a-fA-F-]+')->name('update');
            Route::delete('/{supplier}', [StockController::class, 'suppliersDestroy'])->middleware('tenant.permission:suppliers.delete')->where('supplier', '[0-9a-fA-F-]+')->name('destroy');
        });

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [StockController::class, 'ordersIndex'])->middleware('tenant.permission:orders.read')->name('index');
            Route::get('/create', [StockController::class, 'ordersCreate'])->middleware('tenant.permission:orders.create')->name('create');
            Route::post('/', [StockController::class, 'ordersStore'])->middleware('tenant.permission:orders.create')->name('store');
            Route::get('/data/table', [StockController::class, 'ordersData'])->middleware('tenant.permission:orders.read')->name('data');
            Route::get('/data/search', [StockController::class, 'ordersSearch'])->middleware('tenant.permission:orders.read')->name('search');
            Route::get('/data/{order}', [StockController::class, 'ordersDetail'])->middleware('tenant.permission:orders.read')->where('order', '[0-9a-fA-F-]+')->name('detail');
            Route::get('/export/excel', [StockController::class, 'ordersExportExcel'])->middleware('tenant.permission:orders.export')->name('export.excel');
            Route::get('/{order}', [StockController::class, 'ordersShow'])->middleware('tenant.permission:orders.read')->where('order', '[0-9a-fA-F-]+')->name('show');
            Route::get('/{order}/edit', [StockController::class, 'ordersEdit'])->middleware('tenant.permission:orders.update')->where('order', '[0-9a-fA-F-]+')->name('edit');
            Route::put('/{order}', [StockController::class, 'ordersUpdate'])->middleware('tenant.permission:orders.update')->where('order', '[0-9a-fA-F-]+')->name('update');
            Route::delete('/{order}', [StockController::class, 'ordersDestroy'])->middleware('tenant.permission:orders.delete')->where('order', '[0-9a-fA-F-]+')->name('destroy');
            Route::post('/{order}/receive', [StockController::class, 'ordersReceive'])->middleware('tenant.permission:orders.receive')->where('order', '[0-9a-fA-F-]+')->name('receive');
        });

        Route::prefix('delivery-notes')->name('delivery-notes.')->group(function () {
            Route::get('/', [DeliveryNoteController::class, 'index'])->middleware('tenant.permission:delivery-notes.read')->name('index');
            Route::get('/create', [DeliveryNoteController::class, 'create'])->middleware('tenant.permission:delivery-notes.create')->name('create');
            Route::post('/', [DeliveryNoteController::class, 'store'])->middleware('tenant.permission:delivery-notes.create')->name('store');
            Route::get('/data/table', [DeliveryNoteController::class, 'data'])->middleware('tenant.permission:delivery-notes.read')->name('data');
            Route::get('/export/excel', [DeliveryNoteController::class, 'exportExcel'])->middleware('tenant.permission:delivery-notes.export')->name('export.excel');
            Route::get('/{deliveryNote}', [DeliveryNoteController::class, 'show'])->middleware('tenant.permission:delivery-notes.read')->where('deliveryNote', '[0-9a-fA-F-]+')->name('show');
            Route::get('/{deliveryNote}/edit', [DeliveryNoteController::class, 'edit'])->middleware('tenant.permission:delivery-notes.update')->where('deliveryNote', '[0-9a-fA-F-]+')->name('edit');
            Route::put('/{deliveryNote}', [DeliveryNoteController::class, 'update'])->middleware('tenant.permission:delivery-notes.update')->where('deliveryNote', '[0-9a-fA-F-]+')->name('update');
            Route::delete('/{deliveryNote}', [DeliveryNoteController::class, 'destroy'])->middleware('tenant.permission:delivery-notes.delete')->where('deliveryNote', '[0-9a-fA-F-]+')->name('destroy');
            Route::post('/{deliveryNote}/validate', [DeliveryNoteController::class, 'validateNote'])->middleware('tenant.permission:delivery-notes.manage')->where('deliveryNote', '[0-9a-fA-F-]+')->name('validate');
            Route::post('/{deliveryNote}/cancel', [DeliveryNoteController::class, 'cancel'])->middleware('tenant.permission:delivery-notes.manage')->where('deliveryNote', '[0-9a-fA-F-]+')->name('cancel');
            Route::get('/{deliveryNote}/pdf', [DeliveryNoteController::class, 'downloadPdf'])->middleware('tenant.permission:delivery-notes.export')->where('deliveryNote', '[0-9a-fA-F-]+')->name('pdf');
        });

        Route::prefix('movements')->name('movements.')->group(function () {
            Route::get('/', [StockMovementController::class, 'index'])->middleware('tenant.permission:stock-movements.read')->name('index');
            Route::get('/data/table', [StockMovementController::class, 'data'])->middleware('tenant.permission:stock-movements.read')->name('data');
            Route::get('/export/excel', [StockMovementController::class, 'exportExcel'])->middleware('tenant.permission:stock-movements.export')->name('export.excel');
        });
    });
