<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\GoogleMeet\Http\Controllers\GoogleMeetController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:google-meet', 'tenant.permission:google-meet.view'])
    ->prefix('extensions/google-meet')
    ->name('google-meet.')
    ->group(function () {
        Route::get('/', [GoogleMeetController::class, 'index'])->name('index');
        Route::get('/oauth/connect', [GoogleMeetController::class, 'connect'])->middleware('tenant.permission:google-meet.manage')->name('oauth.connect');
        Route::get('/oauth/callback', [GoogleMeetController::class, 'callback'])->middleware('tenant.permission:google-meet.manage')->name('oauth.callback');
        Route::post('/oauth/disconnect', [GoogleMeetController::class, 'disconnect'])->middleware('tenant.permission:google-meet.manage')->name('oauth.disconnect');
        Route::get('/data/calendars', [GoogleMeetController::class, 'calendarsData'])->name('calendars.data');
        Route::post('/calendar/select', [GoogleMeetController::class, 'selectCalendar'])->middleware('tenant.permission:google-meet.manage')->name('calendar.select');
        Route::get('/data/meetings', [GoogleMeetController::class, 'meetingsData'])->name('meetings.data');
        Route::get('/data/stats', [GoogleMeetController::class, 'stats'])->name('stats');
        Route::post('/sync', [GoogleMeetController::class, 'sync'])->middleware('tenant.permission:google-meet.manage')->name('sync');
        Route::post('/meetings', [GoogleMeetController::class, 'storeMeeting'])->middleware('tenant.permission:google-meet.manage')->name('meetings.store');
        Route::put('/meetings/{calendarId}/{eventId}', [GoogleMeetController::class, 'updateMeeting'])
            ->middleware('tenant.permission:google-meet.manage')
            ->where(['calendarId' => '.+', 'eventId' => '.+'])
            ->name('meetings.update');
        Route::delete('/meetings/{calendarId}/{eventId}', [GoogleMeetController::class, 'destroyMeeting'])
            ->middleware('tenant.permission:google-meet.manage')
            ->where(['calendarId' => '.+', 'eventId' => '.+'])
            ->name('meetings.destroy');
    });
