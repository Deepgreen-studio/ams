<?php

use App\Domains\Integrations\Controllers\DataMappingController;
use App\Domains\Integrations\Controllers\IntegrationConnectionController;
use App\Domains\Integrations\Controllers\IntegrationController;
use App\Domains\Integrations\Controllers\SyncController;
use App\Domains\Integrations\Controllers\WebhookController;
use App\Domains\Integrations\Enums\IntegrationPermission;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/incoming/{webhook}', [WebhookController::class, 'incoming'])
    ->middleware('throttle:webhook-incoming');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('integrations')->group(function (): void {
        Route::get('/', [IntegrationController::class, 'index'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::post('/', [IntegrationController::class, 'store'])
            ->middleware('permission:'.IntegrationPermission::CREATE);

        Route::post('/{integration}/test-connection', [IntegrationConnectionController::class, 'testConnection'])
            ->middleware('permission:'.IntegrationPermission::MANAGE);
        Route::post('/{integration}/test-authentication', [IntegrationConnectionController::class, 'testAuthentication'])
            ->middleware('permission:'.IntegrationPermission::MANAGE);
        Route::post('/{integration}/execute', [IntegrationConnectionController::class, 'execute'])
            ->middleware('permission:'.IntegrationPermission::MANAGE);
        Route::put('/{integration}/configuration', [IntegrationConnectionController::class, 'updateConfiguration'])
            ->middleware('permission:'.IntegrationPermission::UPDATE);
        Route::get('/{integration}/history', [IntegrationConnectionController::class, 'history'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::get('/{integration}/history/{log}', [IntegrationConnectionController::class, 'showHistory'])
            ->middleware('permission:'.IntegrationPermission::VIEW);

        Route::get('/{integration}', [IntegrationController::class, 'show'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::put('/{integration}', [IntegrationController::class, 'update'])
            ->middleware('permission:'.IntegrationPermission::UPDATE);
        Route::delete('/{integration}', [IntegrationController::class, 'destroy'])
            ->middleware('permission:'.IntegrationPermission::DELETE);
        Route::post('/{integration}/restore', [IntegrationController::class, 'restore'])
            ->middleware('permission:'.IntegrationPermission::MANAGE);
    });

    Route::prefix('webhooks')->group(function (): void {
        Route::get('/', [WebhookController::class, 'index'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::post('/', [WebhookController::class, 'store'])
            ->middleware('permission:'.IntegrationPermission::CREATE);
        Route::get('/logs', [WebhookController::class, 'logs'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::get('/logs/{log}', [WebhookController::class, 'showLog'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::post('/logs/{log}/retry', [WebhookController::class, 'retry'])
            ->middleware('permission:'.IntegrationPermission::MANAGE);
        Route::get('/events', [WebhookController::class, 'events'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::get('/events/{event}', [WebhookController::class, 'showEvent'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::get('/{webhook}', [WebhookController::class, 'show'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::put('/{webhook}', [WebhookController::class, 'update'])
            ->middleware('permission:'.IntegrationPermission::UPDATE);
        Route::delete('/{webhook}', [WebhookController::class, 'destroy'])
            ->middleware('permission:'.IntegrationPermission::DELETE);
        Route::post('/{webhook}/test', [WebhookController::class, 'test'])
            ->middleware('permission:'.IntegrationPermission::MANAGE);
    });

    Route::prefix('sync')->group(function (): void {
        Route::get('/dashboard', [SyncController::class, 'dashboard'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::get('/configs', [SyncController::class, 'index'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::post('/configs', [SyncController::class, 'store'])
            ->middleware('permission:'.IntegrationPermission::CREATE);
        Route::get('/configs/{sync}', [SyncController::class, 'show'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::put('/configs/{sync}', [SyncController::class, 'update'])
            ->middleware('permission:'.IntegrationPermission::UPDATE);
        Route::delete('/configs/{sync}', [SyncController::class, 'destroy'])
            ->middleware('permission:'.IntegrationPermission::DELETE);
        Route::post('/configs/{sync}/run', [SyncController::class, 'run'])
            ->middleware('permission:'.IntegrationPermission::MANAGE);
        Route::get('/runs', [SyncController::class, 'runs'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::get('/runs/{run}', [SyncController::class, 'showRun'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::get('/logs', [SyncController::class, 'logs'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
    });

    Route::prefix('mappings')->group(function (): void {
        Route::get('/catalogs', [DataMappingController::class, 'catalogs'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::get('/', [DataMappingController::class, 'index'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::post('/', [DataMappingController::class, 'store'])
            ->middleware('permission:'.IntegrationPermission::CREATE);
        Route::get('/{mapping}', [DataMappingController::class, 'show'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::put('/{mapping}', [DataMappingController::class, 'update'])
            ->middleware('permission:'.IntegrationPermission::UPDATE);
        Route::delete('/{mapping}', [DataMappingController::class, 'destroy'])
            ->middleware('permission:'.IntegrationPermission::DELETE);
        Route::post('/{mapping}/preview', [DataMappingController::class, 'preview'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
        Route::post('/{mapping}/validate', [DataMappingController::class, 'validateMapping'])
            ->middleware('permission:'.IntegrationPermission::VIEW);
    });
});
