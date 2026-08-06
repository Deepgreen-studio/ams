<?php

use App\Domains\Workflows\Controllers\WorkflowController;
use App\Domains\Workflows\Controllers\WorkflowInstanceController;
use App\Domains\Workflows\Enums\WorkflowPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('workflows')->group(function (): void {
    Route::get('/dashboard', [WorkflowController::class, 'dashboard'])
        ->middleware('permission:'.WorkflowPermission::VIEW);
    Route::get('/catalog', [WorkflowController::class, 'catalog'])
        ->middleware('permission:'.WorkflowPermission::VIEW);

    Route::get('/monitor', [WorkflowInstanceController::class, 'monitor'])
        ->middleware('permission:'.WorkflowPermission::VIEW);
    Route::get('/queue', [WorkflowInstanceController::class, 'queue'])
        ->middleware('permission:'.WorkflowPermission::APPROVE);
    Route::get('/history', [WorkflowInstanceController::class, 'history'])
        ->middleware('permission:'.WorkflowPermission::VIEW);
    Route::get('/instances', [WorkflowInstanceController::class, 'index'])
        ->middleware('permission:'.WorkflowPermission::VIEW);
    Route::get('/instances/{instance}', [WorkflowInstanceController::class, 'show'])
        ->middleware('permission:'.WorkflowPermission::VIEW);
    Route::post('/instances/{instance}/approve', [WorkflowInstanceController::class, 'approve'])
        ->middleware('permission:'.WorkflowPermission::APPROVE);
    Route::post('/instances/{instance}/reject', [WorkflowInstanceController::class, 'reject'])
        ->middleware('permission:'.WorkflowPermission::APPROVE);
    Route::post('/instances/{instance}/cancel', [WorkflowInstanceController::class, 'cancel'])
        ->middleware('permission:'.WorkflowPermission::MANAGE);

    Route::get('/', [WorkflowController::class, 'index'])
        ->middleware('permission:'.WorkflowPermission::VIEW);
    Route::post('/', [WorkflowController::class, 'store'])
        ->middleware('permission:'.WorkflowPermission::CREATE);
    Route::get('/{workflow}', [WorkflowController::class, 'show'])
        ->middleware('permission:'.WorkflowPermission::VIEW);
    Route::put('/{workflow}', [WorkflowController::class, 'update'])
        ->middleware('permission:'.WorkflowPermission::UPDATE);
    Route::delete('/{workflow}', [WorkflowController::class, 'destroy'])
        ->middleware('permission:'.WorkflowPermission::DELETE);
    Route::post('/{workflow}/toggle', [WorkflowController::class, 'toggle'])
        ->middleware('permission:'.WorkflowPermission::UPDATE);
    Route::post('/{workflow}/publish', [WorkflowController::class, 'publish'])
        ->middleware('permission:'.WorkflowPermission::UPDATE);
    Route::post('/{workflow}/archive', [WorkflowController::class, 'archive'])
        ->middleware('permission:'.WorkflowPermission::UPDATE);
    Route::post('/{workflow}/start', [WorkflowInstanceController::class, 'start'])
        ->middleware('permission:'.WorkflowPermission::CREATE);
});
