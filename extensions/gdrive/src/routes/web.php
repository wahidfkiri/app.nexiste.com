<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\GoogleDrive\Http\Controllers\GoogleDriveController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:google-drive', 'tenant.permission:google-drive.view'])
    ->prefix('extensions/google-drive')
    ->name('google-drive.')
    ->group(function () {
        Route::get('/', [GoogleDriveController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [GoogleDriveController::class, 'connect'])->middleware('tenant.permission:google-drive.manage')->name('oauth.connect');
        Route::get('/oauth/callback', [GoogleDriveController::class, 'callback'])->middleware('tenant.permission:google-drive.manage')->name('oauth.callback');
        Route::post('/oauth/disconnect', [GoogleDriveController::class, 'disconnect'])->middleware('tenant.permission:google-drive.manage')->name('oauth.disconnect');
        Route::get('/data/files', [GoogleDriveController::class, 'filesData'])->name('files.data');
        Route::get('/data/stats', [GoogleDriveController::class, 'stats'])->name('stats');
        Route::get('/data/trash', [GoogleDriveController::class, 'trashData'])->name('trash.data');
        Route::get('/data/search', [GoogleDriveController::class, 'search'])->name('search');
        Route::post('/folders', [GoogleDriveController::class, 'createFolder'])->middleware('tenant.permission:google-drive.manage')->name('folders.store');
        Route::post('/files/upload', [GoogleDriveController::class, 'upload'])->middleware('tenant.permission:google-drive.manage')->name('files.upload');
        Route::patch('/files/{fileId}/rename', [GoogleDriveController::class, 'rename'])->middleware('tenant.permission:google-drive.manage')->where(['fileId' => '.+'])->name('files.rename');
        Route::patch('/files/{fileId}/move', [GoogleDriveController::class, 'move'])->middleware('tenant.permission:google-drive.manage')->where(['fileId' => '.+'])->name('files.move');
        Route::post('/files/{fileId}/copy', [GoogleDriveController::class, 'copy'])->middleware('tenant.permission:google-drive.manage')->where(['fileId' => '.+'])->name('files.copy');
        Route::post('/files/{fileId}/share', [GoogleDriveController::class, 'share'])->middleware('tenant.permission:google-drive.manage')->where(['fileId' => '.+'])->name('files.share');
        Route::delete('/files/{fileId}', [GoogleDriveController::class, 'delete'])->middleware('tenant.permission:google-drive.manage')->where(['fileId' => '.+'])->name('files.delete');
        Route::post('/files/{fileId}/restore', [GoogleDriveController::class, 'restore'])->middleware('tenant.permission:google-drive.manage')->where(['fileId' => '.+'])->name('files.restore');
        Route::get('/files/{fileId}/download', [GoogleDriveController::class, 'download'])->where(['fileId' => '.+'])->name('files.download');
        Route::delete('/trash', [GoogleDriveController::class, 'emptyTrash'])->middleware('tenant.permission:google-drive.manage')->name('trash.empty');
    });
