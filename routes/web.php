<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\DraftController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GlobalSettingsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecurityValidationDemoController;
use App\Http\Controllers\SuperAdmin\TenantAdminController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
   // Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
   Route::get('/', fn() => view('auth.login')); 

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);

        Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
        Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

        Route::get('/password/reset', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
        Route::post('/password/email', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
        Route::get('/password/reset/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
        Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
    });

    Route::get('/auth/google/desktop/finalize/{token}', [AuthController::class, 'finalizeDesktopGoogle'])->name('auth.google.desktop.finalize');

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify')
        ->middleware('signed');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->name('verification.send');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    Route::middleware(['auth', 'superadmin'])
        ->prefix('superadmin/tenants')
        ->name('superadmin.tenants.')
        ->group(function () {
            Route::get('/', [TenantAdminController::class, 'index'])->name('index');
            Route::post('/', [TenantAdminController::class, 'store'])->name('store');
            Route::get('/data/table', [TenantAdminController::class, 'getData'])->name('data');
            Route::get('/data/stats', [TenantAdminController::class, 'getStats'])->name('stats');
            Route::get('/{tenant}', [TenantAdminController::class, 'show'])->name('show');
            Route::put('/{tenant}', [TenantAdminController::class, 'update'])->name('update');
            Route::post('/{tenant}/status', [TenantAdminController::class, 'updateStatus'])->name('status');
        });

    Route::middleware(['auth', 'tenant'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('tenant.permission:dashboard.read')
            ->name('dashboard');
        Route::get('/home', [HomeController::class, 'index'])
            ->middleware('tenant.permission:home.read')
            ->name('home');

        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding/profile', [OnboardingController::class, 'saveProfile'])->name('onboarding.profile');
        Route::post('/onboarding/company', [OnboardingController::class, 'saveCompany'])->name('onboarding.company');
        Route::post('/onboarding/sector', [OnboardingController::class, 'saveSector'])->name('onboarding.sector');
        Route::post('/onboarding/apps', [OnboardingController::class, 'saveApps'])->name('onboarding.apps');
        Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

        Route::get('/applications', fn () => redirect()->route('marketplace.index'))
            ->middleware('tenant.permission:marketplace.read')
            ->name('applications');
        Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
        Route::get('/settings/global', [GlobalSettingsController::class, 'show'])
            ->middleware('tenant.permission:settings.read')
            ->name('settings.global');
        Route::put('/settings/global', [GlobalSettingsController::class, 'update'])
            ->middleware('tenant.permission:settings.update')
            ->name('settings.global.update');
        Route::post('/settings/global/data-exports', [GlobalSettingsController::class, 'startDataExport'])
            ->middleware('tenant.permission:data-exports.create')
            ->name('settings.global.exports.start');
        Route::get('/settings/global/data-exports/{dataExport}', [GlobalSettingsController::class, 'showDataExport'])
            ->middleware('tenant.permission:data-exports.read')
            ->name('settings.global.exports.show');
        Route::post('/settings/global/data-exports/{dataExport}/process', [GlobalSettingsController::class, 'processDataExport'])
            ->middleware('tenant.permission:data-exports.process')
            ->name('settings.global.exports.process');
        Route::get('/profile-settings', [ProfileController::class, 'show'])->name('profile-settings');
        Route::put('/profile-settings', [ProfileController::class, 'update'])->name('profile-settings.update');
        Route::get('/security/validation-demo', [SecurityValidationDemoController::class, 'create'])->name('security.validation-demo');
        Route::post('/security/validation-demo', [SecurityValidationDemoController::class, 'store'])->name('security.validation-demo.store');
        Route::get('/analytics', fn () => view('analytics'))
            ->middleware('tenant.permission:reports.read')
            ->name('analytics');
        Route::get('/tables', fn () => view('tables'))->name('tables');

        Route::prefix('api/drafts')->name('drafts.')->group(function () {
            Route::post('/save', [DraftController::class, 'save'])->name('save');
            Route::get('/load', [DraftController::class, 'load'])->name('load');
            Route::delete('/delete/{id}', [DraftController::class, 'destroy'])->name('delete');
        });
    });
});
