<?php

use App\Domains\Monitoring\Controllers\MonitoringController;
use App\Domains\Monitoring\Enums\MonitoringPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('monitoring')->group(function (): void {
        Route::get('/dashboard', [MonitoringController::class, 'dashboard'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::get('/api', [MonitoringController::class, 'apiMonitor'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::get('/webhooks', [MonitoringController::class, 'webhookMonitor'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::get('/queue', [MonitoringController::class, 'queueHealth'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::get('/response-history', [MonitoringController::class, 'responseHistory'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::get('/realtime', [MonitoringController::class, 'realtime'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::get('/integrations', [MonitoringController::class, 'integrations'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::get('/timeline', [MonitoringController::class, 'timeline'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::get('/health-checks', [MonitoringController::class, 'healthChecks'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::get('/services', [MonitoringController::class, 'serviceStatuses'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::get('/logs', [MonitoringController::class, 'logs'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::post('/capture', [MonitoringController::class, 'capture'])
            ->middleware('permission:'.MonitoringPermission::MANAGE);

        Route::get('/alerts', [MonitoringController::class, 'alerts'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::post('/alerts', [MonitoringController::class, 'storeAlert'])
            ->middleware('permission:'.MonitoringPermission::MANAGE);
        Route::get('/alerts/{alert}', [MonitoringController::class, 'showAlert'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::put('/alerts/{alert}', [MonitoringController::class, 'updateAlert'])
            ->middleware('permission:'.MonitoringPermission::MANAGE);
        Route::delete('/alerts/{alert}', [MonitoringController::class, 'destroyAlert'])
            ->middleware('permission:'.MonitoringPermission::MANAGE);

        Route::get('/alert-events', [MonitoringController::class, 'alertEvents'])
            ->middleware('permission:'.MonitoringPermission::VIEW);
        Route::post('/alert-events/{event}/acknowledge', [MonitoringController::class, 'acknowledgeEvent'])
            ->middleware('permission:'.MonitoringPermission::MANAGE);
    });
});
