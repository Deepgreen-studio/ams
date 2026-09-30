<?php

use App\Domains\Integrations\Controllers\ConnectorController;
use App\Domains\Integrations\Controllers\DataMappingController;
use App\Domains\Integrations\Controllers\IntegrationConnectionController;
use App\Domains\Integrations\Controllers\IntegrationController;
use App\Domains\Integrations\Controllers\SyncController;
use App\Domains\Integrations\Controllers\WebhookController;
use App\Domains\Integrations\Enums\IntegrationPermission;
use App\Domains\Integrations\Enums\SyncPermission;
use App\Domains\Integrations\Enums\WebhookPermission;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/incoming/{webhook}', [WebhookController::class, 'incoming'])
    ->middleware('throttle:webhook-incoming');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('integrations')->group(function (): void {
        Route::get('/', [IntegrationController::class, 'index'])
            ->middleware('permission:'.IntegrationPermission::VIEW.'|'.IntegrationPermission::VIEW_TRASH);
        Route::get('/connectors', [ConnectorController::class, 'index'])
            ->middleware('permission:' . IntegrationPermission::VIEW);
        Route::post('/', [IntegrationController::class, 'store'])
            ->middleware('permission:' . IntegrationPermission::CREATE);

        Route::post('/{integration}/test-connection', [IntegrationConnectionController::class, 'testConnection'])
            ->middleware('permission:' . IntegrationPermission::MANAGE);
        Route::post('/{integration}/test-authentication', [IntegrationConnectionController::class, 'testAuthentication'])
            ->middleware('permission:' . IntegrationPermission::MANAGE);
        Route::post('/{integration}/execute', [IntegrationConnectionController::class, 'execute'])
            ->middleware('permission:' . IntegrationPermission::MANAGE);
        Route::put('/{integration}/configuration', [IntegrationConnectionController::class, 'updateConfiguration'])
            ->middleware('permission:' . IntegrationPermission::UPDATE);
        Route::get('/{integration}/connector', [ConnectorController::class, 'show'])
            ->middleware('permission:' . IntegrationPermission::VIEW);
        Route::post('/{integration}/connector/oauth/refresh', [ConnectorController::class, 'refreshOauth'])
            ->middleware('permission:' . IntegrationPermission::MANAGE);
        Route::put('/{integration}/connector/credentials', [ConnectorController::class, 'updateCredentials'])
            ->middleware('permission:' . IntegrationPermission::UPDATE);
        Route::get('/{integration}/history', [IntegrationConnectionController::class, 'history'])
            ->middleware('permission:' . IntegrationPermission::VIEW);
        Route::get('/{integration}/history/{log}', [IntegrationConnectionController::class, 'showHistory'])
            ->middleware('permission:' . IntegrationPermission::VIEW);

        Route::get('/{integration}', [IntegrationController::class, 'show'])
            ->middleware('permission:' . IntegrationPermission::VIEW);
        Route::put('/{integration}', [IntegrationController::class, 'update'])
            ->middleware('permission:' . IntegrationPermission::UPDATE);
        Route::delete('/{integration}', [IntegrationController::class, 'destroy'])
            ->middleware('permission:' . IntegrationPermission::DELETE);
        Route::post('/{integration}/restore', [IntegrationController::class, 'restore'])
            ->middleware('permission:'.IntegrationPermission::RESTORE);
    });

    Route::prefix('webhooks')->group(function (): void {
        Route::get('/', [WebhookController::class, 'index'])
            ->middleware('permission:'.WebhookPermission::VIEW.'|'.WebhookPermission::VIEW_TRASH);
        Route::post('/', [WebhookController::class, 'store'])
            ->middleware('permission:'.WebhookPermission::CREATE);
        Route::get('/logs', [WebhookController::class, 'logs'])
            ->middleware('permission:'.WebhookPermission::LOGS);
        Route::get('/logs/{log}', [WebhookController::class, 'showLog'])
            ->middleware('permission:'.WebhookPermission::LOGS);
        Route::post('/logs/{log}/retry', [WebhookController::class, 'retry'])
            ->middleware('permission:'.WebhookPermission::TEST);
        Route::get('/events', [WebhookController::class, 'events'])
            ->middleware('permission:'.WebhookPermission::EVENTS);
        Route::get('/events/{event}', [WebhookController::class, 'showEvent'])
            ->middleware('permission:'.WebhookPermission::EVENTS);
        Route::get('/{webhook}', [WebhookController::class, 'show'])
            ->middleware('permission:'.WebhookPermission::VIEW);
        Route::put('/{webhook}', [WebhookController::class, 'update'])
            ->middleware('permission:'.WebhookPermission::UPDATE);
        Route::delete('/{webhook}', [WebhookController::class, 'destroy'])
            ->middleware('permission:'.WebhookPermission::DELETE);
        Route::post('/{webhook}/restore', [WebhookController::class, 'restore'])
            ->middleware('permission:'.WebhookPermission::RESTORE);
        Route::post('/{webhook}/test', [WebhookController::class, 'test'])
            ->middleware('permission:'.WebhookPermission::TEST);
    });

    Route::prefix('sync')->group(function (): void {
        Route::get('/dashboard', [SyncController::class, 'dashboard'])
            ->middleware('permission:'.SyncPermission::VIEW.'|'.IntegrationPermission::VIEW);
        Route::get('/configs', [SyncController::class, 'index'])
            ->middleware('permission:'.SyncPermission::CONFIGS.'|'.IntegrationPermission::VIEW);
        Route::post('/configs', [SyncController::class, 'store'])
            ->middleware('permission:'.SyncPermission::CONFIGS.'|'.IntegrationPermission::CREATE);
        Route::get('/configs/{sync}', [SyncController::class, 'show'])
            ->middleware('permission:'.SyncPermission::CONFIGS.'|'.IntegrationPermission::VIEW);
        Route::put('/configs/{sync}', [SyncController::class, 'update'])
            ->middleware('permission:'.SyncPermission::CONFIGS.'|'.IntegrationPermission::UPDATE);
        Route::delete('/configs/{sync}', [SyncController::class, 'destroy'])
            ->middleware('permission:'.SyncPermission::CONFIGS.'|'.IntegrationPermission::DELETE);
        Route::post('/configs/{sync}/run', [SyncController::class, 'run'])
            ->middleware('permission:'.SyncPermission::CONFIGS.'|'.IntegrationPermission::MANAGE);
        Route::get('/runs', [SyncController::class, 'runs'])
            ->middleware('permission:'.SyncPermission::HISTORY.'|'.IntegrationPermission::VIEW);
        Route::get('/runs/{run}', [SyncController::class, 'showRun'])
            ->middleware('permission:'.SyncPermission::HISTORY.'|'.IntegrationPermission::VIEW);
        Route::get('/logs', [SyncController::class, 'logs'])
            ->middleware('permission:'.SyncPermission::LOGS.'|'.IntegrationPermission::VIEW);
    });

    Route::prefix('mappings')->group(function (): void {
        Route::get('/catalogs', [DataMappingController::class, 'catalogs'])
            ->middleware('permission:'.SyncPermission::CONFIGS.'|'.IntegrationPermission::VIEW);
        Route::get('/', [DataMappingController::class, 'index'])
            ->middleware('permission:'.SyncPermission::CONFIGS.'|'.IntegrationPermission::VIEW);
        Route::post('/', [DataMappingController::class, 'store'])
            ->middleware('permission:' . IntegrationPermission::CREATE);
        Route::get('/{mapping}', [DataMappingController::class, 'show'])
            ->middleware('permission:' . IntegrationPermission::VIEW);
        Route::put('/{mapping}', [DataMappingController::class, 'update'])
            ->middleware('permission:' . IntegrationPermission::UPDATE);
        Route::delete('/{mapping}', [DataMappingController::class, 'destroy'])
            ->middleware('permission:' . IntegrationPermission::DELETE);
        Route::post('/{mapping}/preview', [DataMappingController::class, 'preview'])
            ->middleware('permission:' . IntegrationPermission::VIEW);
        Route::post('/{mapping}/validate', [DataMappingController::class, 'validateMapping'])
            ->middleware('permission:' . IntegrationPermission::VIEW);
    });
});
