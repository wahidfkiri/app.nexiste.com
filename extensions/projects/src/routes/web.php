<?php

use Illuminate\Support\Facades\Route;
use NexusExtensions\Projects\Http\Controllers\ProjectController;

Route::middleware(['web', 'auth', 'tenant', 'extension.active:projects', 'tenant.permission:projects.view'])
    ->prefix('extensions/projects')
    ->name('projects.')
    ->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/data/list', [ProjectController::class, 'data'])->name('data');
        Route::get('/data/stats', [ProjectController::class, 'stats'])->name('stats');
        Route::post('/', [ProjectController::class, 'store'])->middleware('tenant.permission:projects.create')->name('store');
        Route::get('/{project}', [ProjectController::class, 'show'])->whereNumber('project')->name('show');
        Route::put('/{project}', [ProjectController::class, 'update'])->middleware('tenant.permission:projects.update')->whereNumber('project')->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->middleware('tenant.permission:projects.delete')->whereNumber('project')->name('destroy');
        Route::put('/{project}/members', [ProjectController::class, 'syncMembers'])->middleware('tenant.permission:projects.manage_members')->whereNumber('project')->name('members.sync');
        Route::get('/{project}/tasks/data', [ProjectController::class, 'tasksData'])->whereNumber('project')->name('tasks.data');
        Route::get('/{project}/boards', [ProjectController::class, 'boardsData'])->whereNumber('project')->name('boards.data');
        Route::post('/{project}/tasks', [ProjectController::class, 'storeTask'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->name('tasks.store');
        Route::put('/{project}/tasks/{task}', [ProjectController::class, 'updateTask'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->whereNumber('task')->name('tasks.update');
        Route::patch('/{project}/tasks/{task}/move', [ProjectController::class, 'moveTask'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->whereNumber('task')->name('tasks.move');
        Route::delete('/{project}/tasks/{task}', [ProjectController::class, 'destroyTask'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->whereNumber('task')->name('tasks.destroy');
        Route::post('/{project}/calendar/schedule', [ProjectController::class, 'scheduleProjectCalendar'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->name('calendar.schedule-project');
        Route::post('/{project}/tasks/{task}/calendar/schedule', [ProjectController::class, 'scheduleTaskCalendar'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->whereNumber('task')->name('calendar.schedule-task');
        Route::get('/{project}/tasks/{task}/comments', [ProjectController::class, 'commentsData'])->whereNumber('project')->whereNumber('task')->name('tasks.comments.data');
        Route::post('/{project}/tasks/{task}/comments', [ProjectController::class, 'addComment'])->middleware('tenant.permission:projects.comment')->whereNumber('project')->whereNumber('task')->name('tasks.comments.store');
        Route::put('/{project}/tasks/{task}/comments/{comment}', [ProjectController::class, 'updateComment'])->middleware('tenant.permission:projects.comment')->whereNumber('project')->whereNumber('task')->whereNumber('comment')->name('tasks.comments.update');
        Route::delete('/{project}/tasks/{task}/comments/{comment}', [ProjectController::class, 'destroyComment'])->middleware('tenant.permission:projects.comment')->whereNumber('project')->whereNumber('task')->whereNumber('comment')->name('tasks.comments.destroy');
        Route::post('/{project}/tasks/{task}/checklist', [ProjectController::class, 'checklistStore'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->whereNumber('task')->name('tasks.checklist.store');
        Route::get('/{project}/tasks/{task}/checklist', [ProjectController::class, 'checklistData'])->whereNumber('project')->whereNumber('task')->name('tasks.checklist.data');
        Route::put('/{project}/tasks/{task}/checklist/{item}', [ProjectController::class, 'checklistUpdate'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->whereNumber('task')->whereNumber('item')->name('tasks.checklist.update');
        Route::patch('/{project}/tasks/{task}/checklist/{item}/toggle', [ProjectController::class, 'checklistToggle'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->whereNumber('task')->whereNumber('item')->name('tasks.checklist.toggle');
        Route::delete('/{project}/tasks/{task}/checklist/{item}', [ProjectController::class, 'checklistDestroy'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->whereNumber('task')->whereNumber('item')->name('tasks.checklist.destroy');
        Route::get('/{project}/tasks/{task}/files', [ProjectController::class, 'taskFilesData'])->whereNumber('project')->whereNumber('task')->name('tasks.files.data');
        Route::post('/{project}/tasks/{task}/files', [ProjectController::class, 'taskUploadFile'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->whereNumber('task')->name('tasks.files.upload');
        Route::delete('/{project}/tasks/{task}/files/{file}', [ProjectController::class, 'taskDeleteFile'])->middleware('tenant.permission:projects.manage_tasks')->whereNumber('project')->whereNumber('task')->whereNumber('file')->name('tasks.files.delete');
        Route::get('/{project}/files', [ProjectController::class, 'filesData'])->whereNumber('project')->name('files.data');
        Route::post('/{project}/files', [ProjectController::class, 'uploadFile'])->middleware('tenant.permission:projects.update')->whereNumber('project')->name('files.upload');
        Route::delete('/{project}/files/{file}', [ProjectController::class, 'deleteFile'])->middleware('tenant.permission:projects.update')->whereNumber('project')->whereNumber('file')->name('files.delete');
    });
