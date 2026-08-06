<?php

use App\Domains\Notifications\Controllers\NotificationCenterController;
use App\Domains\Notifications\Controllers\NotificationTemplateController;
use App\Domains\Notifications\Enums\NotificationPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('notifications')->group(function (): void {
    Route::get('/dashboard', [NotificationCenterController::class, 'dashboard'])
        ->middleware('permission:'.NotificationPermission::VIEW);
    Route::get('/center', [NotificationCenterController::class, 'center']);
    Route::get('/unread-count', [NotificationCenterController::class, 'unreadCount']);
    Route::get('/unread', [NotificationCenterController::class, 'unread']);

    Route::get('/preferences', [NotificationCenterController::class, 'preferences']);
    Route::put('/preferences', [NotificationCenterController::class, 'syncPreferences']);

    Route::get('/channels', [NotificationCenterController::class, 'channels'])
        ->middleware('permission:'.NotificationPermission::VIEW);
    Route::put('/channels/{channel}', [NotificationCenterController::class, 'updateChannel'])
        ->middleware('permission:'.NotificationPermission::UPDATE);

    Route::get('/templates/approvals', [NotificationTemplateController::class, 'approvals'])
        ->middleware('permission:'.NotificationPermission::VIEW);
    Route::post('/templates/approvals/{approval}/approve', [NotificationTemplateController::class, 'approve'])
        ->middleware('permission:'.NotificationPermission::APPROVE);
    Route::post('/templates/approvals/{approval}/reject', [NotificationTemplateController::class, 'reject'])
        ->middleware('permission:'.NotificationPermission::APPROVE);

    Route::get('/templates', [NotificationTemplateController::class, 'index'])
        ->middleware('permission:'.NotificationPermission::VIEW);
    Route::post('/templates', [NotificationTemplateController::class, 'store'])
        ->middleware('permission:'.NotificationPermission::CREATE);
    Route::get('/templates/{template}', [NotificationTemplateController::class, 'show'])
        ->middleware('permission:'.NotificationPermission::VIEW);
    Route::put('/templates/{template}', [NotificationTemplateController::class, 'update'])
        ->middleware('permission:'.NotificationPermission::UPDATE);
    Route::delete('/templates/{template}', [NotificationTemplateController::class, 'destroy'])
        ->middleware('permission:'.NotificationPermission::DELETE);
    Route::post('/templates/{template}/preview', [NotificationTemplateController::class, 'preview'])
        ->middleware('permission:'.NotificationPermission::VIEW);
    Route::post('/templates/{template}/test-send', [NotificationTemplateController::class, 'testSend'])
        ->middleware('permission:'.NotificationPermission::UPDATE);
    Route::post('/templates/{template}/submit', [NotificationTemplateController::class, 'submit'])
        ->middleware('permission:'.NotificationPermission::UPDATE);
    Route::post('/templates/{template}/publish', [NotificationTemplateController::class, 'publish'])
        ->middleware('permission:'.NotificationPermission::PUBLISH);
    Route::get('/templates/{template}/versions', [NotificationTemplateController::class, 'versions'])
        ->middleware('permission:'.NotificationPermission::VIEW);
    Route::get('/templates/{template}/versions/compare', [NotificationTemplateController::class, 'compare'])
        ->middleware('permission:'.NotificationPermission::VIEW);
    Route::get('/templates/{template}/versions/{version}', [NotificationTemplateController::class, 'showVersion'])
        ->middleware('permission:'.NotificationPermission::VIEW);
    Route::post('/templates/{template}/versions/{version}/restore', [NotificationTemplateController::class, 'restoreVersion'])
        ->middleware('permission:'.NotificationPermission::UPDATE);

    Route::get('/delivery-logs', [NotificationCenterController::class, 'deliveryLogs'])
        ->middleware('permission:'.NotificationPermission::VIEW);
    Route::get('/logs', [NotificationCenterController::class, 'deliveryLogs'])
        ->middleware('permission:'.NotificationPermission::VIEW);

    Route::get('/', [NotificationCenterController::class, 'index']);
    Route::post('/', [NotificationCenterController::class, 'store'])
        ->middleware('permission:'.NotificationPermission::CREATE);
    Route::post('/read-all', [NotificationCenterController::class, 'markAllRead']);
    Route::post('/{notification}/read', [NotificationCenterController::class, 'markRead']);
    Route::delete('/{notification}', [NotificationCenterController::class, 'destroy']);
});
