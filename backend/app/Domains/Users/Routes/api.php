<?php

use App\Domains\Users\Controllers\UserController;
use App\Domains\Users\Controllers\UserProfileController;
use App\Domains\Users\Enums\UserPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('users')->group(function (): void {
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);
    Route::post('/avatar', [UserProfileController::class, 'uploadAvatar']);

    Route::get('/', [UserController::class, 'index'])
        ->middleware('permission:'.UserPermission::VIEW);
    Route::post('/', [UserController::class, 'store'])
        ->middleware('permission:'.UserPermission::CREATE);
    Route::get('/{user}', [UserController::class, 'show'])
        ->middleware('permission:'.UserPermission::VIEW);
    Route::put('/{user}', [UserController::class, 'update'])
        ->middleware('permission:'.UserPermission::UPDATE);
    Route::delete('/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:'.UserPermission::DELETE);
    Route::post('/{user}/restore', [UserController::class, 'restore'])
        ->middleware('permission:'.UserPermission::RESTORE);
    Route::delete('/{user}/force-delete', [UserController::class, 'forceDelete'])
        ->middleware('permission:'.UserPermission::FORCE_DELETE);
});
