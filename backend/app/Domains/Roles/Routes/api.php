<?php

use App\Domains\Roles\Controllers\PermissionController;
use App\Domains\Roles\Controllers\RoleController;
use App\Domains\Roles\Controllers\UserRoleController;
use App\Domains\Roles\Enums\RolePermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('roles')->group(function (): void {
        Route::get('/', [RoleController::class, 'index'])
            ->middleware('permission:'.RolePermission::VIEW);
        Route::post('/', [RoleController::class, 'store'])
            ->middleware('permission:'.RolePermission::CREATE);
        Route::get('/{role}', [RoleController::class, 'show'])
            ->middleware('permission:'.RolePermission::VIEW);
        Route::put('/{role}', [RoleController::class, 'update'])
            ->middleware('permission:'.RolePermission::UPDATE);
        Route::delete('/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:'.RolePermission::DELETE);
        Route::post('/{role}/restore', [RoleController::class, 'restore'])
            ->middleware('permission:'.RolePermission::RESTORE);
        Route::delete('/{role}/force-delete', [RoleController::class, 'forceDelete'])
            ->middleware('permission:'.RolePermission::FORCE_DELETE);
        Route::post('/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->middleware('permission:'.RolePermission::ASSIGN);
    });

    Route::prefix('permissions')->group(function (): void {
        Route::get('/', [PermissionController::class, 'index'])
            ->middleware('permission:'.RolePermission::VIEW);
        Route::get('/groups', [PermissionController::class, 'groups'])
            ->middleware('permission:'.RolePermission::VIEW);
        Route::get('/matrix', [PermissionController::class, 'matrix'])
            ->middleware('permission:'.RolePermission::VIEW);
    });

    Route::post('/users/{user}/roles', [UserRoleController::class, 'store'])
        ->middleware('permission:'.RolePermission::ASSIGN_USERS);
    Route::delete('/users/{user}/roles/{role}', [UserRoleController::class, 'destroy'])
        ->middleware('permission:'.RolePermission::ASSIGN_USERS);
});
