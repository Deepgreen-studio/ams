<?php

use App\Domains\Analytics\Controllers\AnalyticsController;
use App\Domains\Analytics\Controllers\AnalyticsDashboardController;
use App\Domains\Analytics\Controllers\AnalyticsEventController;
use App\Domains\Analytics\Controllers\AnalyticsOverviewController;
use App\Domains\Analytics\Controllers\AnalyticsReportController;
use App\Domains\Analytics\Controllers\AnalyticsWidgetController;
use App\Domains\Analytics\Controllers\BusinessAnalyticsController;
use App\Domains\Analytics\Controllers\ExecutiveAnalyticsController;
use App\Domains\Analytics\Controllers\SecurityAnalyticsController;
use App\Domains\Analytics\Enums\AnalyticsPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('analytics')->group(function (): void {
    // Enterprise foundation
    Route::get('/overview', [AnalyticsOverviewController::class, 'overview'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/categories', [AnalyticsOverviewController::class, 'categories'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);

    Route::get('/events/summary', [AnalyticsEventController::class, 'summary'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/events', [AnalyticsEventController::class, 'index'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::post('/events', [AnalyticsEventController::class, 'store'])
        ->middleware('permission:'.AnalyticsPermission::CREATE);
    Route::get('/events/{event}', [AnalyticsEventController::class, 'show'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);

    // Dashboard builder
    Route::get('/widgets/library', [AnalyticsWidgetController::class, 'library'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);

    Route::get('/dashboards/templates', [AnalyticsDashboardController::class, 'templates'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/dashboards', [AnalyticsDashboardController::class, 'index'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::post('/dashboards', [AnalyticsDashboardController::class, 'store'])
        ->middleware('permission:'.AnalyticsPermission::CREATE);
    Route::post('/dashboards/{dashboard}/from-template', [AnalyticsDashboardController::class, 'fromTemplate'])
        ->middleware('permission:'.AnalyticsPermission::CREATE);
    Route::get('/dashboards/{dashboard}', [AnalyticsDashboardController::class, 'show'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::put('/dashboards/{dashboard}', [AnalyticsDashboardController::class, 'update'])
        ->middleware('permission:'.AnalyticsPermission::UPDATE);
    Route::put('/dashboards/{dashboard}/layout', [AnalyticsDashboardController::class, 'saveLayout'])
        ->middleware('permission:'.AnalyticsPermission::UPDATE);
    Route::delete('/dashboards/{dashboard}', [AnalyticsDashboardController::class, 'destroy'])
        ->middleware('permission:'.AnalyticsPermission::DELETE);
    Route::post('/dashboards/{dashboard}/duplicate', [AnalyticsDashboardController::class, 'duplicate'])
        ->middleware('permission:'.AnalyticsPermission::CREATE);
    Route::get('/dashboards/{dashboard}/data', [AnalyticsDashboardController::class, 'data'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);

    Route::get('/dashboards/{dashboard}/shares', [AnalyticsDashboardController::class, 'shares'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::post('/dashboards/{dashboard}/shares', [AnalyticsDashboardController::class, 'share'])
        ->middleware('permission:'.AnalyticsPermission::UPDATE);
    Route::delete('/dashboards/{dashboard}/shares/{share}', [AnalyticsDashboardController::class, 'revokeShare'])
        ->middleware('permission:'.AnalyticsPermission::UPDATE);

    Route::post('/dashboards/{dashboard}/widgets', [AnalyticsWidgetController::class, 'store'])
        ->middleware('permission:'.AnalyticsPermission::UPDATE);
    Route::put('/widgets/{widget}', [AnalyticsWidgetController::class, 'update'])
        ->middleware('permission:'.AnalyticsPermission::UPDATE);
    Route::delete('/widgets/{widget}', [AnalyticsWidgetController::class, 'destroy'])
        ->middleware('permission:'.AnalyticsPermission::DELETE);

    // Enterprise report builder
    Route::get('/reports', [AnalyticsReportController::class, 'index'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::post('/reports', [AnalyticsReportController::class, 'store'])
        ->middleware('permission:'.AnalyticsPermission::CREATE);
    Route::get('/reports/{report}', [AnalyticsReportController::class, 'show'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::put('/reports/{report}', [AnalyticsReportController::class, 'update'])
        ->middleware('permission:'.AnalyticsPermission::UPDATE);
    Route::put('/reports/{report}/designer', [AnalyticsReportController::class, 'designer'])
        ->middleware('permission:'.AnalyticsPermission::UPDATE);
    Route::post('/reports/{report}/preview', [AnalyticsReportController::class, 'preview'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::post('/reports/{report}/run', [AnalyticsReportController::class, 'run'])
        ->middleware('permission:'.AnalyticsPermission::EXPORT);
    Route::get('/reports/{report}/runs', [AnalyticsReportController::class, 'runs'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/reports/{report}/runs/{run}/download', [AnalyticsReportController::class, 'downloadRun'])
        ->middleware('permission:'.AnalyticsPermission::EXPORT);
    Route::put('/reports/{report}/schedule', [AnalyticsReportController::class, 'schedule'])
        ->middleware('permission:'.AnalyticsPermission::UPDATE);
    Route::delete('/reports/{report}', [AnalyticsReportController::class, 'destroy'])
        ->middleware('permission:'.AnalyticsPermission::DELETE);

    // Phase 9.5 — Customer / Business Analytics
    Route::get('/business/overview', [BusinessAnalyticsController::class, 'overview'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/business/customers', [BusinessAnalyticsController::class, 'customers'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/business/revenue', [BusinessAnalyticsController::class, 'revenue'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/business/applications', [BusinessAnalyticsController::class, 'applications'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/business/growth', [BusinessAnalyticsController::class, 'growth'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/business/forecast', [BusinessAnalyticsController::class, 'forecast'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::post('/business/capture', [BusinessAnalyticsController::class, 'capture'])
        ->middleware('permission:'.AnalyticsPermission::MANAGE);

    // Phase 9.6 — Audit & Security Analytics
    Route::get('/security/overview', [SecurityAnalyticsController::class, 'overview'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/security/audit', [SecurityAnalyticsController::class, 'audit'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/security/dashboard', [SecurityAnalyticsController::class, 'security'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/security/timeline', [SecurityAnalyticsController::class, 'timeline'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/security/risk', [SecurityAnalyticsController::class, 'risk'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/security/heatmap', [SecurityAnalyticsController::class, 'heatmap'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/security/export', [SecurityAnalyticsController::class, 'export'])
        ->middleware('permission:'.AnalyticsPermission::EXPORT);
    Route::post('/security/capture', [SecurityAnalyticsController::class, 'capture'])
        ->middleware('permission:'.AnalyticsPermission::MANAGE);

    // Phase 9.7 — Executive Dashboard
    Route::get('/executive/overview', [ExecutiveAnalyticsController::class, 'overview'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/executive/ceo', [ExecutiveAnalyticsController::class, 'ceo'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/executive/admin', [ExecutiveAnalyticsController::class, 'admin'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/executive/operations', [ExecutiveAnalyticsController::class, 'operations'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/executive/compliance', [ExecutiveAnalyticsController::class, 'compliance'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/executive/support', [ExecutiveAnalyticsController::class, 'support'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/executive/customer', [ExecutiveAnalyticsController::class, 'customer'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/executive/scorecards', [ExecutiveAnalyticsController::class, 'scorecards'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/executive/trends', [ExecutiveAnalyticsController::class, 'trends'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/executive/forecast', [ExecutiveAnalyticsController::class, 'forecast'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/executive/widgets', [ExecutiveAnalyticsController::class, 'widgets'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::post('/executive/capture', [ExecutiveAnalyticsController::class, 'capture'])
        ->middleware('permission:'.AnalyticsPermission::MANAGE);

    // Existing platform operational analytics (Phase 8.7)
    Route::get('/dashboard', [AnalyticsController::class, 'dashboard'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/notifications', [AnalyticsController::class, 'notifications'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/automation', [AnalyticsController::class, 'automation'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/workflows', [AnalyticsController::class, 'workflows'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/ai', [AnalyticsController::class, 'ai'])
        ->middleware('permission:'.AnalyticsPermission::VIEW);
    Route::get('/export', [AnalyticsController::class, 'export'])
        ->middleware('permission:'.AnalyticsPermission::EXPORT);
});
