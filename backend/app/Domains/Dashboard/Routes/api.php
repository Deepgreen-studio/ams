<?php

use App\Domains\Dashboard\Controllers\DashboardController;
use App\Domains\Dashboard\Enums\DashboardPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'show'])
        ->middleware('permission:'.DashboardPermission::VIEW);
});
