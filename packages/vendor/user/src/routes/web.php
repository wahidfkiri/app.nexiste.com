<?php

use Illuminate\Support\Facades\Route;
use Vendor\User\Http\Controllers\UserController;

Route::middleware(['web'])->group(function () {
    Route::get('/invitation/{token}', [UserController::class, 'acceptForm'])->name('users.accept');
    Route::post('/invitation/{token}', [UserController::class, 'acceptSubmit'])->name('users.accept.submit');
});

Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('users')
    ->name('users.')
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('tenant.permission:users.read')->name('index');
        Route::get('/invite', [UserController::class, 'create'])->middleware('tenant.permission:users.invite')->name('create');
        Route::post('/invite', [UserController::class, 'store'])->middleware('tenant.permission:users.invite')->name('store');

        Route::get('/data/table', [UserController::class, 'getData'])->middleware('tenant.permission:users.read')->name('data');
        Route::get('/data/stats', [UserController::class, 'getStats'])->middleware('tenant.permission:users.read')->name('stats');

        Route::post('/bulk/delete', [UserController::class, 'bulkDelete'])->middleware('tenant.permission:users.delete')->name('bulk.delete');
        Route::post('/bulk/status', [UserController::class, 'bulkStatus'])->middleware('tenant.permission:users.update')->name('bulk.status');

        Route::get('/export/csv', [UserController::class, 'exportCsv'])->middleware('tenant.permission:users.export')->name('export.csv');
        Route::get('/export/excel', [UserController::class, 'exportExcel'])->middleware('tenant.permission:users.export')->name('export.excel');

        Route::get('/invitations/list', [UserController::class, 'invitations'])->middleware('tenant.permission:users.invite')->name('invitations');
        Route::get('/invitations/data', [UserController::class, 'invitationsData'])->middleware('tenant.permission:users.invite')->name('invitations.data');
        Route::post('/invitations/{invitation}/resend', [UserController::class, 'resendInvitation'])->middleware('tenant.permission:users.invite')->whereNumber('invitation')->name('invitations.resend');
        Route::delete('/invitations/{invitation}', [UserController::class, 'revokeInvitation'])->middleware('tenant.permission:users.invite')->whereNumber('invitation')->name('invitations.revoke');

        Route::get('/{user}', [UserController::class, 'show'])->middleware('tenant.permission:users.read')->where('user', '[0-9a-fA-F-]+')->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->middleware('tenant.permission:users.update')->where('user', '[0-9a-fA-F-]+')->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->middleware('tenant.permission:users.update')->where('user', '[0-9a-fA-F-]+')->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('tenant.permission:users.delete')->where('user', '[0-9a-fA-F-]+')->name('destroy');
        Route::post('/{user}/suspend', [UserController::class, 'suspend'])->middleware('tenant.permission:users.update')->where('user', '[0-9a-fA-F-]+')->name('suspend');
        Route::post('/{user}/activate', [UserController::class, 'activate'])->middleware('tenant.permission:users.update')->where('user', '[0-9a-fA-F-]+')->name('activate');
        Route::post('/{user}/avatar', [UserController::class, 'uploadAvatar'])->middleware('tenant.permission:users.update')->where('user', '[0-9a-fA-F-]+')->name('avatar');
    });
