<?php

use App\Domains\Companies\Controllers\CompanyController;
use App\Domains\Companies\Controllers\DepartmentController;
use App\Domains\Companies\Controllers\LocationController;
use App\Domains\Companies\Controllers\TeamController;
use App\Domains\Companies\Enums\CompanyPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('companies')->group(function (): void {
        Route::get('/', [CompanyController::class, 'index'])
            ->middleware('permission:'.CompanyPermission::VIEW);
        Route::post('/', [CompanyController::class, 'store'])
            ->middleware('permission:'.CompanyPermission::CREATE);
        Route::get('/{company}', [CompanyController::class, 'show'])
            ->middleware('permission:'.CompanyPermission::VIEW);
        Route::put('/{company}', [CompanyController::class, 'update'])
            ->middleware('permission:'.CompanyPermission::UPDATE);
        Route::delete('/{company}', [CompanyController::class, 'destroy'])
            ->middleware('permission:'.CompanyPermission::DELETE);
        Route::post('/{company}/restore', [CompanyController::class, 'restore'])
            ->middleware('permission:'.CompanyPermission::RESTORE);
        Route::post('/{company}/logo', [CompanyController::class, 'uploadLogo'])
            ->middleware('permission:'.CompanyPermission::MANAGE);
        Route::post('/{company}/favicon', [CompanyController::class, 'uploadFavicon'])
            ->middleware('permission:'.CompanyPermission::MANAGE);
        Route::put('/{company}/branding', [CompanyController::class, 'updateBranding'])
            ->middleware('permission:'.CompanyPermission::MANAGE);
    });

    Route::prefix('departments')->group(function (): void {
        Route::get('/', [DepartmentController::class, 'index'])
            ->middleware('permission:'.CompanyPermission::VIEW);
        Route::post('/', [DepartmentController::class, 'store'])
            ->middleware('permission:'.CompanyPermission::UPDATE);
        Route::put('/{department}', [DepartmentController::class, 'update'])
            ->middleware('permission:'.CompanyPermission::UPDATE);
        Route::delete('/{department}', [DepartmentController::class, 'destroy'])
            ->middleware('permission:'.CompanyPermission::UPDATE);
    });

    Route::prefix('teams')->group(function (): void {
        Route::get('/', [TeamController::class, 'index'])
            ->middleware('permission:'.CompanyPermission::VIEW);
        Route::post('/', [TeamController::class, 'store'])
            ->middleware('permission:'.CompanyPermission::UPDATE);
        Route::put('/{team}', [TeamController::class, 'update'])
            ->middleware('permission:'.CompanyPermission::UPDATE);
        Route::delete('/{team}', [TeamController::class, 'destroy'])
            ->middleware('permission:'.CompanyPermission::UPDATE);
    });

    Route::prefix('company-locations')->group(function (): void {
        Route::get('/', [LocationController::class, 'index'])
            ->middleware('permission:'.CompanyPermission::VIEW);
        Route::post('/', [LocationController::class, 'store'])
            ->middleware('permission:'.CompanyPermission::UPDATE);
        Route::put('/{company_location}', [LocationController::class, 'update'])
            ->middleware('permission:'.CompanyPermission::UPDATE);
        Route::delete('/{company_location}', [LocationController::class, 'destroy'])
            ->middleware('permission:'.CompanyPermission::UPDATE);
    });
});
