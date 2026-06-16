<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\NotionWorkspace\Http\Controllers\NotionApiController;
use NexusExtensions\NotionWorkspace\Http\Controllers\NotionLinkController;
use NexusExtensions\NotionWorkspace\Http\Controllers\NotionWorkspaceController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:notion-workspace', 'tenant.permission:notion.view'])
    ->prefix('extensions/notion-workspace')
    ->name('notion-workspace.')
    ->group(function () {
        Route::get('/', [NotionWorkspaceController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [NotionWorkspaceController::class, 'connect'])->middleware('tenant.permission:notion.admin')->name('connect');
        Route::get('/oauth/callback', [NotionWorkspaceController::class, 'callback'])->middleware('tenant.permission:notion.admin')->name('callback');
        Route::post('/disconnect', [NotionWorkspaceController::class, 'disconnect'])->middleware('tenant.permission:notion.admin')->name('disconnect');
        Route::get('/pages/search', [NotionApiController::class, 'pages'])->name('pages.search');
        Route::post('/pages', [NotionApiController::class, 'store'])->middleware('tenant.permission:notion.create')->name('pages.store');
        Route::get('/pages/{pageId}', [NotionApiController::class, 'show'])->where('pageId', '[A-Za-z0-9\-]+')->name('pages.show');
        Route::get('/links', [NotionLinkController::class, 'index'])->name('links.index');
        Route::post('/links', [NotionLinkController::class, 'store'])->middleware('tenant.permission:notion.share')->name('links.store');
        Route::put('/links/{link}', [NotionLinkController::class, 'update'])->middleware('tenant.permission:notion.share')->whereNumber('link')->name('links.update');
        Route::delete('/links/{link}', [NotionLinkController::class, 'destroy'])->middleware('tenant.permission:notion.share')->whereNumber('link')->name('links.destroy');
    });
