<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\GoogleSheets\Http\Controllers\GoogleSheetsController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:google-sheets', 'tenant.permission:google-sheets.view'])
    ->prefix('extensions/google-sheets')
    ->name('google-sheets.')
    ->group(function () {
        Route::get('/', [GoogleSheetsController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [GoogleSheetsController::class, 'connect'])->middleware('tenant.permission:google-sheets.manage')->name('oauth.connect');
        Route::get('/oauth/callback', [GoogleSheetsController::class, 'callback'])->middleware('tenant.permission:google-sheets.manage')->name('oauth.callback');
        Route::post('/oauth/disconnect', [GoogleSheetsController::class, 'disconnect'])->middleware('tenant.permission:google-sheets.manage')->name('oauth.disconnect');
        Route::get('/data/stats', [GoogleSheetsController::class, 'stats'])->name('stats');
        Route::get('/data/spreadsheets', [GoogleSheetsController::class, 'spreadsheetsData'])->name('spreadsheets.data');
        Route::post('/spreadsheets', [GoogleSheetsController::class, 'createSpreadsheet'])->middleware('tenant.permission:google-sheets.manage')->name('spreadsheets.store');
        Route::get('/spreadsheets/{spreadsheetId}', [GoogleSheetsController::class, 'showSpreadsheet'])->where(['spreadsheetId' => '.+'])->name('spreadsheets.show');
        Route::patch('/spreadsheets/{spreadsheetId}/rename', [GoogleSheetsController::class, 'renameSpreadsheet'])->middleware('tenant.permission:google-sheets.manage')->where(['spreadsheetId' => '.+'])->name('spreadsheets.rename');
        Route::post('/spreadsheets/{spreadsheetId}/duplicate', [GoogleSheetsController::class, 'duplicateSpreadsheet'])->middleware('tenant.permission:google-sheets.manage')->where(['spreadsheetId' => '.+'])->name('spreadsheets.duplicate');
        Route::delete('/spreadsheets/{spreadsheetId}', [GoogleSheetsController::class, 'deleteSpreadsheet'])->middleware('tenant.permission:google-sheets.manage')->where(['spreadsheetId' => '.+'])->name('spreadsheets.delete');
        Route::post('/spreadsheets/{spreadsheetId}/sheets', [GoogleSheetsController::class, 'addSheet'])->middleware('tenant.permission:google-sheets.manage')->where(['spreadsheetId' => '.+'])->name('sheets.store');
        Route::patch('/spreadsheets/{spreadsheetId}/sheets/{sheetId}/rename', [GoogleSheetsController::class, 'renameSheet'])->middleware('tenant.permission:google-sheets.manage')->where(['spreadsheetId' => '.+'])->name('sheets.rename');
        Route::delete('/spreadsheets/{spreadsheetId}/sheets/{sheetId}', [GoogleSheetsController::class, 'deleteSheet'])->middleware('tenant.permission:google-sheets.manage')->where(['spreadsheetId' => '.+'])->name('sheets.delete');
        Route::get('/spreadsheets/{spreadsheetId}/values', [GoogleSheetsController::class, 'readRange'])->where(['spreadsheetId' => '.+'])->name('values.read');
        Route::put('/spreadsheets/{spreadsheetId}/values', [GoogleSheetsController::class, 'writeRange'])->middleware('tenant.permission:google-sheets.manage')->where(['spreadsheetId' => '.+'])->name('values.write');
        Route::post('/spreadsheets/{spreadsheetId}/values/append', [GoogleSheetsController::class, 'appendRows'])->middleware('tenant.permission:google-sheets.manage')->where(['spreadsheetId' => '.+'])->name('values.append');
        Route::delete('/spreadsheets/{spreadsheetId}/values', [GoogleSheetsController::class, 'clearRange'])->middleware('tenant.permission:google-sheets.manage')->where(['spreadsheetId' => '.+'])->name('values.clear');
        Route::post('/spreadsheets/{spreadsheetId}/values/batch-read', [GoogleSheetsController::class, 'batchRead'])->where(['spreadsheetId' => '.+'])->name('values.batch-read');
        Route::post('/spreadsheets/{spreadsheetId}/values/batch-write', [GoogleSheetsController::class, 'batchWrite'])->middleware('tenant.permission:google-sheets.manage')->where(['spreadsheetId' => '.+'])->name('values.batch-write');
    });
