<?php

use App\Domains\Applications\Controllers\ApplicationAnalyticsController;
use App\Domains\Applications\Controllers\ApplicationConfigurationController;
use App\Domains\Applications\Controllers\ApplicationController;
use App\Domains\Applications\Controllers\ApplicationEnvironmentController;
use App\Domains\Applications\Controllers\ApplicationMonitoringController;
use App\Domains\Applications\Controllers\ApplicationReleaseController;
use App\Domains\Applications\Controllers\ApplicationVersionController;
use App\Domains\Applications\Enums\ApplicationPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('applications')->group(function (): void {
        Route::get('/', [ApplicationController::class, 'index'])
            ->middleware('permission:'.ApplicationPermission::VIEW);
        Route::post('/', [ApplicationController::class, 'store'])
            ->middleware('permission:'.ApplicationPermission::CREATE);

        Route::prefix('{application}/versions')->group(function (): void {
            Route::get('/', [ApplicationVersionController::class, 'index'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::post('/', [ApplicationVersionController::class, 'store'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::get('/compare', [ApplicationVersionController::class, 'compare'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/timeline', [ApplicationVersionController::class, 'timeline'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/history', [ApplicationVersionController::class, 'history'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/{version}', [ApplicationVersionController::class, 'show'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::put('/{version}', [ApplicationVersionController::class, 'update'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::delete('/{version}', [ApplicationVersionController::class, 'destroy'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
        });

        Route::prefix('{application}/environments')->group(function (): void {
            Route::get('/dashboard', [ApplicationEnvironmentController::class, 'dashboard'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/', [ApplicationEnvironmentController::class, 'index'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::post('/', [ApplicationEnvironmentController::class, 'store'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::get('/{environment}', [ApplicationEnvironmentController::class, 'show'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::put('/{environment}', [ApplicationEnvironmentController::class, 'update'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::delete('/{environment}', [ApplicationEnvironmentController::class, 'destroy'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/{environment}/switch', [ApplicationEnvironmentController::class, 'switch'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/{environment}/health-check', [ApplicationEnvironmentController::class, 'healthCheck'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
        });

        Route::prefix('{application}/configurations')->group(function (): void {
            Route::get('/catalog', [ApplicationConfigurationController::class, 'catalog'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/manager', [ApplicationConfigurationController::class, 'manager'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::post('/validate', [ApplicationConfigurationController::class, 'validatePayload'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/', [ApplicationConfigurationController::class, 'index'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::post('/', [ApplicationConfigurationController::class, 'store'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::get('/{configuration}', [ApplicationConfigurationController::class, 'show'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::put('/{configuration}', [ApplicationConfigurationController::class, 'update'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::delete('/{configuration}', [ApplicationConfigurationController::class, 'destroy'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::get('/{configuration}/history', [ApplicationConfigurationController::class, 'history'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::post('/{configuration}/history/{history}/restore', [ApplicationConfigurationController::class, 'restoreHistory'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/{configuration}/feature-flags', [ApplicationConfigurationController::class, 'upsertFeatureFlag'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/{configuration}/feature-flags/{flag}/toggle', [ApplicationConfigurationController::class, 'toggleFeatureFlag'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
        });

        Route::prefix('{application}/releases')->group(function (): void {
            Route::get('/dashboard', [ApplicationReleaseController::class, 'dashboard'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/calendar', [ApplicationReleaseController::class, 'calendar'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/timeline', [ApplicationReleaseController::class, 'timeline'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/', [ApplicationReleaseController::class, 'index'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::post('/', [ApplicationReleaseController::class, 'store'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::get('/{release}', [ApplicationReleaseController::class, 'show'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::put('/{release}', [ApplicationReleaseController::class, 'update'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::delete('/{release}', [ApplicationReleaseController::class, 'destroy'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/{release}/schedule', [ApplicationReleaseController::class, 'schedule'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/{release}/submit-approval', [ApplicationReleaseController::class, 'submitApproval'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/{release}/approve', [ApplicationReleaseController::class, 'approve'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/{release}/reject', [ApplicationReleaseController::class, 'reject'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/{release}/deploy', [ApplicationReleaseController::class, 'deploy'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/{release}/rollback', [ApplicationReleaseController::class, 'rollback'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
        });

        Route::prefix('{application}/monitoring')->group(function (): void {
            Route::get('/crash-dashboard', [ApplicationMonitoringController::class, 'crashDashboard'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/health-dashboard', [ApplicationMonitoringController::class, 'healthDashboard'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/charts', [ApplicationMonitoringController::class, 'charts'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/device-statistics', [ApplicationMonitoringController::class, 'deviceStatistics'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/crashes', [ApplicationMonitoringController::class, 'crashes'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::post('/crashes', [ApplicationMonitoringController::class, 'storeCrash'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::get('/crashes/{crash}', [ApplicationMonitoringController::class, 'showCrash'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::put('/crashes/{crash}', [ApplicationMonitoringController::class, 'updateCrash'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::delete('/crashes/{crash}', [ApplicationMonitoringController::class, 'destroyCrash'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/ingest/crash', [ApplicationMonitoringController::class, 'ingestCrash'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/ingest/anr', [ApplicationMonitoringController::class, 'ingestCrash'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/ingest/api-error', [ApplicationMonitoringController::class, 'ingestApiError'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/ingest/health', [ApplicationMonitoringController::class, 'ingestHealth'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/health/refresh', [ApplicationMonitoringController::class, 'refreshHealth'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::get('/alerts', [ApplicationMonitoringController::class, 'alerts'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::post('/alerts', [ApplicationMonitoringController::class, 'storeAlert'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::put('/alerts/{alert}', [ApplicationMonitoringController::class, 'updateAlert'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::delete('/alerts/{alert}', [ApplicationMonitoringController::class, 'destroyAlert'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
            Route::post('/alert-events/{event}/acknowledge', [ApplicationMonitoringController::class, 'acknowledgeAlertEvent'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
        });

        Route::prefix('{application}/analytics')->group(function (): void {
            Route::get('/dashboard', [ApplicationAnalyticsController::class, 'dashboard'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/trends', [ApplicationAnalyticsController::class, 'trends'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/heatmap', [ApplicationAnalyticsController::class, 'heatmap'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/countries', [ApplicationAnalyticsController::class, 'countries'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::get('/devices', [ApplicationAnalyticsController::class, 'devices'])
                ->middleware('permission:'.ApplicationPermission::VIEW);
            Route::post('/ingest', [ApplicationAnalyticsController::class, 'ingest'])
                ->middleware('permission:'.ApplicationPermission::UPDATE);
        });

        Route::get('/{application}', [ApplicationController::class, 'show'])
            ->middleware('permission:'.ApplicationPermission::VIEW);
        Route::put('/{application}', [ApplicationController::class, 'update'])
            ->middleware('permission:'.ApplicationPermission::UPDATE);
        Route::delete('/{application}', [ApplicationController::class, 'destroy'])
            ->middleware('permission:'.ApplicationPermission::DELETE);
        Route::post('/{application}/restore', [ApplicationController::class, 'restore'])
            ->middleware('permission:'.ApplicationPermission::DELETE);
    });
});
