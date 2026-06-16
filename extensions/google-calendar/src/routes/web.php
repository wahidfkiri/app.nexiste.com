<?php

use Illuminate\Support\Facades\Route;
use Vendor\GoogleCalendar\Http\Controllers\GoogleCalendarController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:google-calendar', 'tenant.permission:google-calendar.view'])
    ->prefix('extensions/google-calendar')
    ->name('google-calendar.')
    ->group(function () {
        Route::get('/', [GoogleCalendarController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [GoogleCalendarController::class, 'connect'])->middleware('tenant.permission:google-calendar.manage')->name('oauth.connect');
        Route::get('/oauth/callback', [GoogleCalendarController::class, 'callback'])->middleware('tenant.permission:google-calendar.manage')->name('oauth.callback');
        Route::post('/oauth/disconnect', [GoogleCalendarController::class, 'disconnect'])->middleware('tenant.permission:google-calendar.manage')->name('oauth.disconnect');
        Route::get('/data/calendars', [GoogleCalendarController::class, 'calendarsData'])->name('calendars.data');
        Route::post('/calendar/select', [GoogleCalendarController::class, 'selectCalendar'])->middleware('tenant.permission:google-calendar.manage')->name('calendar.select');
        Route::get('/data/events', [GoogleCalendarController::class, 'eventsData'])->name('events.data');
        Route::get('/data/stats', [GoogleCalendarController::class, 'stats'])->name('stats');
        Route::post('/sync', [GoogleCalendarController::class, 'sync'])->middleware('tenant.permission:google-calendar.manage')->name('sync');
        Route::post('/events', [GoogleCalendarController::class, 'storeEvent'])->middleware('tenant.permission:google-calendar.manage')->name('events.store');
        Route::put('/events/{calendarId}/{eventId}', [GoogleCalendarController::class, 'updateEvent'])
            ->middleware('tenant.permission:google-calendar.manage')
            ->where(['calendarId' => '.+', 'eventId' => '.+'])
            ->name('events.update');
        Route::delete('/events/{calendarId}/{eventId}', [GoogleCalendarController::class, 'destroyEvent'])
            ->middleware('tenant.permission:google-calendar.manage')
            ->where(['calendarId' => '.+', 'eventId' => '.+'])
            ->name('events.destroy');
    });
