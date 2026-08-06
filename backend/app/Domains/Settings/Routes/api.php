<?php

use App\Domains\Settings\Controllers\FolderController;
use App\Domains\Settings\Controllers\MediaController;
use App\Domains\Settings\Controllers\SettingController;
use App\Domains\Settings\Enums\SettingPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('settings')->group(function (): void {
        Route::get('/', [SettingController::class, 'index'])
            ->middleware('permission:'.SettingPermission::VIEW);
        Route::put('/', [SettingController::class, 'update'])
            ->middleware('permission:'.SettingPermission::UPDATE);

        Route::get('/email', [SettingController::class, 'showEmail'])
            ->middleware('permission:'.SettingPermission::VIEW);
        Route::put('/email', [SettingController::class, 'updateEmail'])
            ->middleware('permission:'.SettingPermission::UPDATE);

        Route::get('/storage', [SettingController::class, 'showStorage'])
            ->middleware('permission:'.SettingPermission::VIEW);
        Route::put('/storage', [SettingController::class, 'updateStorage'])
            ->middleware('permission:'.SettingPermission::UPDATE);

        Route::get('/security', [SettingController::class, 'showSecurity'])
            ->middleware('permission:'.SettingPermission::VIEW);
        Route::put('/security', [SettingController::class, 'updateSecurity'])
            ->middleware('permission:'.SettingPermission::UPDATE);

        Route::get('/api', [SettingController::class, 'showApi'])
            ->middleware('permission:'.SettingPermission::VIEW);
        Route::put('/api', [SettingController::class, 'updateApi'])
            ->middleware('permission:'.SettingPermission::UPDATE);

        Route::get('/queue', [SettingController::class, 'showQueue'])
            ->middleware('permission:'.SettingPermission::VIEW);
        Route::put('/queue', [SettingController::class, 'updateQueue'])
            ->middleware('permission:'.SettingPermission::UPDATE);

        Route::get('/cache', [SettingController::class, 'showCache'])
            ->middleware('permission:'.SettingPermission::VIEW);

        Route::get('/system-info', [SettingController::class, 'systemInfo'])
            ->middleware('permission:'.SettingPermission::VIEW);

        Route::post('/refresh-cache', [SettingController::class, 'refreshConfiguration'])
            ->middleware('permission:'.SettingPermission::UPDATE);
    });

    Route::prefix('media')->group(function (): void {
        Route::get('/', [MediaController::class, 'index'])
            ->middleware('permission:'.SettingPermission::VIEW);
        Route::post('/', [MediaController::class, 'store'])
            ->middleware('permission:'.SettingPermission::MANAGE.'|'.SettingPermission::UPDATE);
        Route::delete('/{media}', [MediaController::class, 'destroy'])
            ->middleware('permission:'.SettingPermission::MANAGE.'|'.SettingPermission::UPDATE);
    });

    Route::prefix('folders')->group(function (): void {
        Route::get('/', [FolderController::class, 'index'])
            ->middleware('permission:'.SettingPermission::VIEW);
        Route::post('/', [FolderController::class, 'store'])
            ->middleware('permission:'.SettingPermission::MANAGE.'|'.SettingPermission::UPDATE);
        Route::put('/{folder}', [FolderController::class, 'update'])
            ->middleware('permission:'.SettingPermission::MANAGE.'|'.SettingPermission::UPDATE);
        Route::delete('/{folder}', [FolderController::class, 'destroy'])
            ->middleware('permission:'.SettingPermission::MANAGE.'|'.SettingPermission::UPDATE);
    });
});
