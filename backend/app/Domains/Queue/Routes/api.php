<?php

use App\Domains\Queue\Controllers\QueueController;
use App\Domains\Queue\Enums\QueuePermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('queue')->group(function (): void {
        Route::get('/dashboard', [QueueController::class, 'dashboard'])
            ->middleware('permission:'.QueuePermission::VIEW);
        Route::get('/statistics', [QueueController::class, 'statistics'])
            ->middleware('permission:'.QueuePermission::VIEW);
        Route::get('/tracks', [QueueController::class, 'tracks'])
            ->middleware('permission:'.QueuePermission::VIEW);
        Route::get('/running', [QueueController::class, 'running'])
            ->middleware('permission:'.QueuePermission::VIEW);
        Route::get('/pending', [QueueController::class, 'pending'])
            ->middleware('permission:'.QueuePermission::VIEW);
        Route::get('/failed', [QueueController::class, 'failed'])
            ->middleware('permission:'.QueuePermission::VIEW);
        Route::post('/failed/retry-all', [QueueController::class, 'retryAllFailed'])
            ->middleware('permission:'.QueuePermission::RETRY.'|'.QueuePermission::MANAGE);
        Route::delete('/failed', [QueueController::class, 'flushFailed'])
            ->middleware('permission:'.QueuePermission::MANAGE);
        Route::get('/failed/{failed}', [QueueController::class, 'showFailed'])
            ->middleware('permission:'.QueuePermission::VIEW);
        Route::post('/failed/{failed}/retry', [QueueController::class, 'retryFailed'])
            ->middleware('permission:'.QueuePermission::RETRY.'|'.QueuePermission::MANAGE);
        Route::delete('/failed/{failed}', [QueueController::class, 'forgetFailed'])
            ->middleware('permission:'.QueuePermission::MANAGE);
        Route::post('/restart', [QueueController::class, 'restart'])
            ->middleware('permission:'.QueuePermission::MANAGE);
        Route::post('/sample', [QueueController::class, 'dispatchSample'])
            ->middleware('permission:'.QueuePermission::MANAGE);
    });
});
