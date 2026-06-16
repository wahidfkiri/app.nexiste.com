<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\Chatbot\Http\Controllers\ChatbotController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:chatbot', 'tenant.permission:chatbot.view'])
    ->prefix('extensions/chatbot')
    ->name('chatbot.')
    ->group(function () {
        Route::get('/', [ChatbotController::class, 'index'])->name('index');
        Route::get('/data/rooms', [ChatbotController::class, 'roomsData'])->name('rooms.data');
        Route::get('/data/users', [ChatbotController::class, 'usersData'])->name('users.data');
        Route::post('/rooms', [ChatbotController::class, 'storeRoom'])->middleware('tenant.permission:chatbot.manage')->name('rooms.store');
        Route::put('/rooms/{room}', [ChatbotController::class, 'updateRoom'])->middleware('tenant.permission:chatbot.manage')->whereNumber('room')->name('rooms.update');
        Route::delete('/rooms/{room}', [ChatbotController::class, 'destroyRoom'])->middleware('tenant.permission:chatbot.manage')->whereNumber('room')->name('rooms.destroy');
        Route::get('/data/messages', [ChatbotController::class, 'messagesData'])->name('messages.data');
        Route::get('/data/search', [ChatbotController::class, 'searchData'])->name('search.data');
        Route::post('/messages/send', [ChatbotController::class, 'sendMessage'])->middleware('tenant.permission:chatbot.manage')->name('messages.send');
        Route::delete('/messages/{message}', [ChatbotController::class, 'destroyMessage'])->middleware('tenant.permission:chatbot.manage')->whereNumber('message')->name('messages.destroy');
        Route::get('/data/stats', [ChatbotController::class, 'stats'])->name('stats');
    });
