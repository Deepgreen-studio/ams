<?php

use App\Domains\Audit\Controllers\ActivityLogController;
use App\Domains\Audit\Controllers\ApiLogController;
use App\Domains\Audit\Controllers\AuditLogController;
use App\Domains\Audit\Controllers\ErrorLogController;
use App\Domains\Audit\Controllers\LoginHistoryController;
use App\Domains\Audit\Controllers\SystemEventController;
use App\Domains\Audit\Enums\AuditPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('activity-logs')->group(function (): void {
        Route::get('/', [ActivityLogController::class, 'index'])
            ->middleware('permission:'.AuditPermission::VIEW);
        Route::get('/export', [ActivityLogController::class, 'export'])
            ->middleware('permission:'.AuditPermission::EXPORT.'|'.AuditPermission::MANAGE);
        Route::get('/{activityLog}', [ActivityLogController::class, 'show'])
            ->middleware('permission:'.AuditPermission::VIEW);
    });

    Route::prefix('audit-logs')->group(function (): void {
        Route::get('/', [AuditLogController::class, 'index'])
            ->middleware('permission:'.AuditPermission::VIEW);
        Route::get('/{auditLog}', [AuditLogController::class, 'show'])
            ->middleware('permission:'.AuditPermission::VIEW);
    });

    Route::get('/login-history', [LoginHistoryController::class, 'index'])
        ->middleware('permission:'.AuditPermission::VIEW);

    Route::get('/system-events', [SystemEventController::class, 'index'])
        ->middleware('permission:'.AuditPermission::VIEW);

    Route::get('/api-logs', [ApiLogController::class, 'index'])
        ->middleware('permission:'.AuditPermission::VIEW);

    Route::get('/error-logs', [ErrorLogController::class, 'index'])
        ->middleware('permission:'.AuditPermission::VIEW);
});
