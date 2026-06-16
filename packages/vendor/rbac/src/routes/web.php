<?php

use Illuminate\Support\Facades\Route;
use Vendor\Rbac\Http\Controllers\RbacController;

Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('rbac')
    ->name('rbac.')
    ->group(function () {
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RbacController::class, 'rolesIndex'])->middleware('tenant.permission:roles.read')->name('index');
            Route::get('/create', [RbacController::class, 'rolesCreate'])->middleware('tenant.permission:roles.manage')->name('create');
            Route::post('/', [RbacController::class, 'rolesStore'])->middleware('tenant.permission:roles.manage')->name('store');
            Route::get('/data/table', [RbacController::class, 'rolesData'])->middleware('tenant.permission:roles.read')->name('data');
            Route::get('/data/stats', [RbacController::class, 'stats'])->middleware('tenant.permission:roles.read')->name('stats');
            Route::get('/{role}', [RbacController::class, 'rolesShow'])->middleware('tenant.permission:roles.read')->whereNumber('role')->name('show');
            Route::get('/{role}/edit', [RbacController::class, 'rolesEdit'])->middleware('tenant.permission:roles.manage')->whereNumber('role')->name('edit');
            Route::put('/{role}', [RbacController::class, 'rolesUpdate'])->middleware('tenant.permission:roles.manage')->whereNumber('role')->name('update');
            Route::delete('/{role}', [RbacController::class, 'rolesDestroy'])->middleware('tenant.permission:roles.manage')->whereNumber('role')->name('destroy');
            Route::post('/{role}/sync-permissions', [RbacController::class, 'rolesSync'])->middleware('tenant.permission:roles.manage')->whereNumber('role')->name('sync');
        });

        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [RbacController::class, 'permissionsIndex'])->middleware('tenant.permission:permissions.read')->name('index');
        });

        Route::post('/users/{user}/assign-role', [RbacController::class, 'assignRole'])
            ->middleware('tenant.permission:roles.manage')
            ->whereNumber('user')
            ->name('users.assign-role');
    });
