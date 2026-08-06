<?php

use App\Domains\Scheduler\Controllers\ScheduledJobController;
use App\Domains\Scheduler\Enums\SchedulerPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('scheduler')->group(function (): void {
    Route::get('/dashboard', [ScheduledJobController::class, 'dashboard'])
        ->middleware('permission:'.SchedulerPermission::VIEW);
    Route::get('/catalog', [ScheduledJobController::class, 'catalog'])
        ->middleware('permission:'.SchedulerPermission::VIEW);
    Route::get('/statistics', [ScheduledJobController::class, 'statistics'])
        ->middleware('permission:'.SchedulerPermission::VIEW);

    Route::get('/history', [ScheduledJobController::class, 'history'])
        ->middleware('permission:'.SchedulerPermission::VIEW);
    Route::get('/running', [ScheduledJobController::class, 'running'])
        ->middleware('permission:'.SchedulerPermission::VIEW);
    Route::get('/failed', [ScheduledJobController::class, 'failed'])
        ->middleware('permission:'.SchedulerPermission::VIEW);
    Route::get('/logs', [ScheduledJobController::class, 'logs'])
        ->middleware('permission:'.SchedulerPermission::VIEW);

    Route::get('/runs/{run}', [ScheduledJobController::class, 'showRun'])
        ->middleware('permission:'.SchedulerPermission::VIEW);
    Route::post('/runs/{run}/retry', [ScheduledJobController::class, 'retry'])
        ->middleware('permission:'.SchedulerPermission::RETRY);

    Route::get('/jobs', [ScheduledJobController::class, 'index'])
        ->middleware('permission:'.SchedulerPermission::VIEW);
    Route::post('/jobs', [ScheduledJobController::class, 'store'])
        ->middleware('permission:'.SchedulerPermission::CREATE);
    Route::get('/jobs/{job}', [ScheduledJobController::class, 'show'])
        ->middleware('permission:'.SchedulerPermission::VIEW);
    Route::put('/jobs/{job}', [ScheduledJobController::class, 'update'])
        ->middleware('permission:'.SchedulerPermission::UPDATE);
    Route::delete('/jobs/{job}', [ScheduledJobController::class, 'destroy'])
        ->middleware('permission:'.SchedulerPermission::DELETE);
    Route::post('/jobs/{job}/toggle', [ScheduledJobController::class, 'toggle'])
        ->middleware('permission:'.SchedulerPermission::UPDATE);
    Route::post('/jobs/{job}/run', [ScheduledJobController::class, 'runNow'])
        ->middleware('permission:'.SchedulerPermission::MANAGE);
});
