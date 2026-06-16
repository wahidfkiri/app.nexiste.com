<?php

use Illuminate\Support\Facades\Route;
use Vendor\User\Http\Controllers\Api\UserApiController;

Route::middleware(['api', 'auth:sanctum', 'tenant'])
    ->prefix('api/v1/users')
    ->name('api.users.')
    ->group(function () {
        Route::get('/', [UserApiController::class, 'index'])->middleware('tenant.permission:users.read')->name('index');
        Route::get('/stats', [UserApiController::class, 'stats'])->middleware('tenant.permission:users.read')->name('stats');
        Route::post('/invite', [UserApiController::class, 'invite'])->middleware('tenant.permission:users.invite')->name('invite');
        Route::get('/{user}', [UserApiController::class, 'show'])->middleware('tenant.permission:users.read')->whereNumber('user')->name('show');
        Route::put('/{user}', [UserApiController::class, 'update'])->middleware('tenant.permission:users.update')->whereNumber('user')->name('update');
        Route::delete('/{user}', [UserApiController::class, 'destroy'])->middleware('tenant.permission:users.delete')->whereNumber('user')->name('destroy');
    });
