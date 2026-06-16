<?php

use Illuminate\Support\Facades\Route;
use Modules\TrelloIntegration\Http\Controllers\TrelloController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:trello-integration', 'tenant.permission:trello.view'])
    ->prefix('extensions/trello-integration')
    ->name('trello-integration.')
    ->group(function () {
        Route::get('/', [TrelloController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [TrelloController::class, 'connect'])->middleware('tenant.permission:trello.manage')->name('connect');
        Route::get('/oauth/callback', [TrelloController::class, 'callback'])->middleware('tenant.permission:trello.manage')->name('callback');
        Route::post('/oauth/finalize', [TrelloController::class, 'finalizeOauth'])->middleware('tenant.permission:trello.manage')->name('oauth.finalize');
        Route::post('/disconnect', [TrelloController::class, 'disconnect'])->middleware('tenant.permission:trello.manage')->name('disconnect');
        Route::post('/sync', [TrelloController::class, 'sync'])->middleware('tenant.permission:trello.manage')->name('sync');

        Route::get('/boards/{board}', [TrelloController::class, 'board'])->whereNumber('board')->name('boards.show');
        Route::get('/cards/{card}', [TrelloController::class, 'showCard'])->whereNumber('card')->name('cards.show');
        Route::put('/cards/{card}', [TrelloController::class, 'updateCard'])->middleware('tenant.permission:trello.manage')->whereNumber('card')->name('cards.update');
        Route::put('/cards/{card}/move', [TrelloController::class, 'moveCard'])->middleware('tenant.permission:trello.manage')->whereNumber('card')->name('cards.move');
        Route::delete('/cards/{card}', [TrelloController::class, 'archiveCard'])->middleware('tenant.permission:trello.manage')->whereNumber('card')->name('cards.archive');
        Route::post('/lists/{list}/cards', [TrelloController::class, 'storeCard'])->middleware('tenant.permission:trello.manage')->whereNumber('list')->name('lists.cards.store');
    });
