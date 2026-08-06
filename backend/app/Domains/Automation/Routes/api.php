<?php

use App\Domains\Automation\Controllers\AutomationRuleController;
use App\Domains\Automation\Enums\AutomationPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('automation')->group(function (): void {
    Route::get('/dashboard', [AutomationRuleController::class, 'dashboard'])
        ->middleware('permission:'.AutomationPermission::VIEW);
    Route::get('/catalog', [AutomationRuleController::class, 'catalog'])
        ->middleware('permission:'.AutomationPermission::VIEW);
    Route::get('/logs', [AutomationRuleController::class, 'logs'])
        ->middleware('permission:'.AutomationPermission::VIEW);

    Route::get('/rules', [AutomationRuleController::class, 'index'])
        ->middleware('permission:'.AutomationPermission::VIEW);
    Route::post('/rules', [AutomationRuleController::class, 'store'])
        ->middleware('permission:'.AutomationPermission::CREATE);
    Route::get('/rules/{rule}', [AutomationRuleController::class, 'show'])
        ->middleware('permission:'.AutomationPermission::VIEW);
    Route::put('/rules/{rule}', [AutomationRuleController::class, 'update'])
        ->middleware('permission:'.AutomationPermission::UPDATE);
    Route::delete('/rules/{rule}', [AutomationRuleController::class, 'destroy'])
        ->middleware('permission:'.AutomationPermission::DELETE);
    Route::post('/rules/{rule}/toggle', [AutomationRuleController::class, 'toggle'])
        ->middleware('permission:'.AutomationPermission::UPDATE);
    Route::post('/rules/{rule}/test', [AutomationRuleController::class, 'test'])
        ->middleware('permission:'.AutomationPermission::UPDATE);
});
