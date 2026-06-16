<?php

use Illuminate\Support\Facades\Route;
use Vendor\Automation\Http\Controllers\AutomationSuggestionController;

Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('automation')
    ->name('automation.')
    ->group(function () {
        Route::get('/suggestions', [AutomationSuggestionController::class, 'index'])->middleware('tenant.permission:automation.read')->name('suggestions.index');
        Route::post('/suggestions/accept', [AutomationSuggestionController::class, 'bulkAccept'])->middleware('tenant.permission:automation.manage')->name('suggestions.accept.bulk');
        Route::post('/suggestions/reject', [AutomationSuggestionController::class, 'bulkReject'])->middleware('tenant.permission:automation.manage')->name('suggestions.reject.bulk');
        Route::post('/suggestions/{suggestion}/accept', [AutomationSuggestionController::class, 'accept'])->middleware('tenant.permission:automation.manage')->whereNumber('suggestion')->name('suggestions.accept');
        Route::post('/suggestions/{suggestion}/reject', [AutomationSuggestionController::class, 'reject'])->middleware('tenant.permission:automation.manage')->whereNumber('suggestion')->name('suggestions.reject');
    });
