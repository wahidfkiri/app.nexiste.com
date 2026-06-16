<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\GoogleGmail\Http\Controllers\GoogleGmailController;

Route::middleware(['web'])
    ->prefix('extensions/google-gmail')
    ->name('google-gmail.')
    ->group(function () {
        Route::get('/oauth/callback', [GoogleGmailController::class, 'callback'])->name('oauth.callback');
    });

Route::middleware(['web', 'auth', 'tenant', 'extension.active:google-gmail', 'tenant.permission:google-gmail.view'])
    ->prefix('extensions/google-gmail')
    ->name('google-gmail.')
    ->group(function () {
        Route::get('/', [GoogleGmailController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [GoogleGmailController::class, 'connect'])->middleware('tenant.permission:google-gmail.manage')->name('oauth.connect');
        Route::post('/oauth/disconnect', [GoogleGmailController::class, 'disconnect'])->middleware('tenant.permission:google-gmail.manage')->name('oauth.disconnect');
        Route::get('/data/stats', [GoogleGmailController::class, 'stats'])->name('stats');
        Route::get('/data/snapshot', [GoogleGmailController::class, 'snapshotData'])->name('snapshot.data');
        Route::get('/data/labels', [GoogleGmailController::class, 'labelsData'])->name('labels.data');
        Route::get('/data/messages', [GoogleGmailController::class, 'messagesData'])->name('messages.data');
        Route::get('/data/settings', [GoogleGmailController::class, 'settingsData'])->name('settings.data');
        Route::post('/data/settings', [GoogleGmailController::class, 'saveSettings'])->middleware('tenant.permission:google-gmail.manage')->name('settings.save');
        Route::get('/threads/{threadId}', [GoogleGmailController::class, 'showThread'])->where(['threadId' => '[^/]+'])->name('threads.show');
        Route::get('/messages/{messageId}', [GoogleGmailController::class, 'showMessage'])->where(['messageId' => '[^/]+'])->name('messages.show');
        Route::post('/messages/send', [GoogleGmailController::class, 'sendEmail'])->middleware('tenant.permission:google-gmail.send')->name('messages.send');
        Route::post('/messages/{messageId}/reply', [GoogleGmailController::class, 'replyEmail'])->middleware('tenant.permission:google-gmail.send')->where(['messageId' => '[^/]+'])->name('messages.reply');
        Route::post('/messages/{messageId}/forward', [GoogleGmailController::class, 'forwardEmail'])->middleware('tenant.permission:google-gmail.send')->where(['messageId' => '[^/]+'])->name('messages.forward');
        Route::post('/messages/{messageId}/mark-read', [GoogleGmailController::class, 'markRead'])->middleware('tenant.permission:google-gmail.manage')->where(['messageId' => '[^/]+'])->name('messages.mark-read');
        Route::post('/messages/{messageId}/mark-unread', [GoogleGmailController::class, 'markUnread'])->middleware('tenant.permission:google-gmail.manage')->where(['messageId' => '[^/]+'])->name('messages.mark-unread');
        Route::post('/messages/{messageId}/star', [GoogleGmailController::class, 'star'])->middleware('tenant.permission:google-gmail.manage')->where(['messageId' => '[^/]+'])->name('messages.star');
        Route::post('/messages/{messageId}/unstar', [GoogleGmailController::class, 'unstar'])->middleware('tenant.permission:google-gmail.manage')->where(['messageId' => '[^/]+'])->name('messages.unstar');
        Route::post('/messages/{messageId}/archive', [GoogleGmailController::class, 'archive'])->middleware('tenant.permission:google-gmail.manage')->where(['messageId' => '[^/]+'])->name('messages.archive');
        Route::post('/messages/{messageId}/trash', [GoogleGmailController::class, 'trash'])->middleware('tenant.permission:google-gmail.manage')->where(['messageId' => '[^/]+'])->name('messages.trash');
        Route::post('/messages/{messageId}/untrash', [GoogleGmailController::class, 'untrash'])->middleware('tenant.permission:google-gmail.manage')->where(['messageId' => '[^/]+'])->name('messages.untrash');
        Route::delete('/messages/{messageId}', [GoogleGmailController::class, 'deleteMessage'])->middleware('tenant.permission:google-gmail.manage')->where(['messageId' => '[^/]+'])->name('messages.delete');
        Route::get('/messages/{messageId}/attachments/{attachmentId}/download', [GoogleGmailController::class, 'downloadAttachment'])
            ->where(['messageId' => '[^/]+', 'attachmentId' => '[^/]+'])
            ->name('messages.attachments.download');
    });
