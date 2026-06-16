<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\Slack\Http\Controllers\SlackController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:slack', 'tenant.permission:slack.view'])
    ->prefix('extensions/slack')
    ->name('slack.')
    ->group(function () {
        Route::get('/', [SlackController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [SlackController::class, 'connect'])->middleware('tenant.permission:slack.manage')->name('oauth.connect');
        Route::get('/oauth/callback', [SlackController::class, 'callback'])->middleware('tenant.permission:slack.manage')->name('oauth.callback');
        Route::post('/oauth/disconnect', [SlackController::class, 'disconnect'])->middleware('tenant.permission:slack.manage')->name('oauth.disconnect');
        Route::get('/data/channels', [SlackController::class, 'channelsData'])->name('channels.data');
        Route::post('/channel/select', [SlackController::class, 'selectChannel'])->middleware('tenant.permission:slack.manage')->name('channel.select');
        Route::get('/data/messages', [SlackController::class, 'messagesData'])->name('messages.data');
        Route::post('/messages/send', [SlackController::class, 'sendMessage'])->middleware('tenant.permission:slack.send')->name('messages.send');
        Route::get('/data/stats', [SlackController::class, 'stats'])->name('stats');
        Route::post('/sync', [SlackController::class, 'sync'])->middleware('tenant.permission:slack.manage')->name('sync');
    });
