<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\Dropbox\Http\Controllers\DropboxController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:dropbox', 'tenant.permission:dropbox.view'])
    ->prefix('extensions/dropbox')
    ->name('dropbox.')
    ->group(function () {
        Route::get('/', [DropboxController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [DropboxController::class, 'connect'])->middleware('tenant.permission:dropbox.manage')->name('oauth.connect');
        Route::get('/oauth/callback', [DropboxController::class, 'callback'])->middleware('tenant.permission:dropbox.manage')->name('oauth.callback');
        Route::post('/oauth/disconnect', [DropboxController::class, 'disconnect'])->middleware('tenant.permission:dropbox.manage')->name('oauth.disconnect');
        Route::get('/data/files', [DropboxController::class, 'filesData'])->name('files.data');
        Route::get('/data/stats', [DropboxController::class, 'stats'])->name('stats');
        Route::get('/data/trash', [DropboxController::class, 'trashData'])->name('trash.data');
        Route::get('/data/search', [DropboxController::class, 'search'])->name('search');
        Route::post('/folders', [DropboxController::class, 'createFolder'])->middleware('tenant.permission:dropbox.manage')->name('folders.store');
        Route::post('/files/upload', [DropboxController::class, 'upload'])->middleware('tenant.permission:dropbox.manage')->name('files.upload');
        Route::patch('/files/{fileId}/rename', [DropboxController::class, 'rename'])->middleware('tenant.permission:dropbox.manage')->where(['fileId' => '.+'])->name('files.rename');
        Route::patch('/files/{fileId}/move', [DropboxController::class, 'move'])->middleware('tenant.permission:dropbox.manage')->where(['fileId' => '.+'])->name('files.move');
        Route::post('/files/{fileId}/copy', [DropboxController::class, 'copy'])->middleware('tenant.permission:dropbox.manage')->where(['fileId' => '.+'])->name('files.copy');
        Route::post('/files/{fileId}/share', [DropboxController::class, 'share'])->middleware('tenant.permission:dropbox.manage')->where(['fileId' => '.+'])->name('files.share');
        Route::get('/files/{fileId}/open', [DropboxController::class, 'open'])->where(['fileId' => '.+'])->name('files.open');
        Route::delete('/files/{fileId}', [DropboxController::class, 'delete'])->middleware('tenant.permission:dropbox.manage')->where(['fileId' => '.+'])->name('files.delete');
        Route::post('/files/{fileId}/restore', [DropboxController::class, 'restore'])->middleware('tenant.permission:dropbox.manage')->where(['fileId' => '.+'])->name('files.restore');
        Route::get('/files/{fileId}/download', [DropboxController::class, 'download'])->where(['fileId' => '.+'])->name('files.download');
        Route::delete('/trash', [DropboxController::class, 'emptyTrash'])->middleware('tenant.permission:dropbox.manage')->name('trash.empty');
    });
