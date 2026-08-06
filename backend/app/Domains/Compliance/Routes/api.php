<?php

use App\Domains\Compliance\Controllers\ComplianceAnalyticsController;
use App\Domains\Compliance\Controllers\ComplianceCaseController;
use App\Domains\Compliance\Controllers\ConsentController;
use App\Domains\Compliance\Controllers\DataBreachController;
use App\Domains\Compliance\Controllers\DpiaController;
use App\Domains\Compliance\Controllers\PolicyDocumentController;
use App\Domains\Compliance\Controllers\PrivacyRequestController;
use App\Domains\Compliance\Enums\CompliancePermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('compliance')->group(function (): void {
        Route::get('/dashboard', [ComplianceCaseController::class, 'dashboard'])
            ->middleware('permission:'.CompliancePermission::VIEW);

        Route::prefix('cases')->group(function (): void {
            Route::get('/', [ComplianceCaseController::class, 'index'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/', [ComplianceCaseController::class, 'store'])
                ->middleware('permission:'.CompliancePermission::CREATE);
            Route::get('/{case}', [ComplianceCaseController::class, 'show'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::put('/{case}', [ComplianceCaseController::class, 'update'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::delete('/{case}', [ComplianceCaseController::class, 'destroy'])
                ->middleware('permission:'.CompliancePermission::DELETE);
            Route::post('/{case}/restore', [ComplianceCaseController::class, 'restore'])
                ->middleware('permission:'.CompliancePermission::DELETE);
        });

        Route::get('/privacy-requests/dashboard', [PrivacyRequestController::class, 'dashboard'])
            ->middleware('permission:'.CompliancePermission::VIEW);

        Route::prefix('privacy-requests')->group(function (): void {
            Route::get('/', [PrivacyRequestController::class, 'index'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/', [PrivacyRequestController::class, 'store'])
                ->middleware('permission:'.CompliancePermission::CREATE);
            Route::get('/{privacyRequest}', [PrivacyRequestController::class, 'show'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::put('/{privacyRequest}', [PrivacyRequestController::class, 'update'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::delete('/{privacyRequest}', [PrivacyRequestController::class, 'destroy'])
                ->middleware('permission:'.CompliancePermission::DELETE);
            Route::post('/{privacyRequest}/restore', [PrivacyRequestController::class, 'restore'])
                ->middleware('permission:'.CompliancePermission::DELETE);
            Route::get('/{privacyRequest}/timeline', [PrivacyRequestController::class, 'timeline'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/{privacyRequest}/verify-identity', [PrivacyRequestController::class, 'verifyIdentity'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{privacyRequest}/approve', [PrivacyRequestController::class, 'approve'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{privacyRequest}/reject', [PrivacyRequestController::class, 'reject'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{privacyRequest}/export', [PrivacyRequestController::class, 'export'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::get('/{privacyRequest}/export/download', [PrivacyRequestController::class, 'downloadExport'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/{privacyRequest}/confirm-deletion', [PrivacyRequestController::class, 'confirmDeletion'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{privacyRequest}/complete', [PrivacyRequestController::class, 'complete'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
        });

        Route::get('/consents/dashboard', [ConsentController::class, 'dashboard'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::get('/consents/types', [ConsentController::class, 'types'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::post('/consents/types', [ConsentController::class, 'storeType'])
            ->middleware('permission:'.CompliancePermission::MANAGE);
        Route::get('/consents/history', [ConsentController::class, 'history'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::get('/consents/preferences', [ConsentController::class, 'preferenceCenter'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::post('/consents/preferences', [ConsentController::class, 'savePreferences'])
            ->middleware('permission:'.CompliancePermission::CREATE);

        Route::prefix('consents')->group(function (): void {
            Route::get('/', [ConsentController::class, 'index'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/', [ConsentController::class, 'store'])
                ->middleware('permission:'.CompliancePermission::CREATE);
            Route::get('/{consent}', [ConsentController::class, 'show'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/{consent}/withdraw', [ConsentController::class, 'withdraw'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::get('/{consent}/timeline', [ConsentController::class, 'timeline'])
                ->middleware('permission:'.CompliancePermission::VIEW);
        });

        Route::get('/breaches/dashboard', [DataBreachController::class, 'dashboard'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::get('/breaches/risk-matrix', [DataBreachController::class, 'riskMatrix'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::get('/breaches/notifications', [DataBreachController::class, 'notificationCenter'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::get('/breaches/reports', [DataBreachController::class, 'reports'])
            ->middleware('permission:'.CompliancePermission::VIEW);

        Route::prefix('breaches')->group(function (): void {
            Route::get('/', [DataBreachController::class, 'index'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/', [DataBreachController::class, 'store'])
                ->middleware('permission:'.CompliancePermission::CREATE);
            Route::get('/{breach}', [DataBreachController::class, 'show'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::put('/{breach}', [DataBreachController::class, 'update'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::delete('/{breach}', [DataBreachController::class, 'destroy'])
                ->middleware('permission:'.CompliancePermission::DELETE);
            Route::post('/{breach}/restore', [DataBreachController::class, 'restore'])
                ->middleware('permission:'.CompliancePermission::DELETE);
            Route::get('/{breach}/timeline', [DataBreachController::class, 'timeline'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/{breach}/assess', [DataBreachController::class, 'assess'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{breach}/contain', [DataBreachController::class, 'contain'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{breach}/recover', [DataBreachController::class, 'recover'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{breach}/root-cause', [DataBreachController::class, 'rootCause'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{breach}/lessons-learned', [DataBreachController::class, 'lessonsLearned'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::put('/{breach}/affected-users', [DataBreachController::class, 'affectedUsers'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{breach}/close', [DataBreachController::class, 'close'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{breach}/actions', [DataBreachController::class, 'storeAction'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{breach}/notifications', [DataBreachController::class, 'storeNotification'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{breach}/notifications/{notification}/send', [DataBreachController::class, 'sendNotification'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
        });

        Route::get('/dpia/dashboard', [DpiaController::class, 'dashboard'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::get('/dpia/risk-matrix', [DpiaController::class, 'riskMatrix'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::get('/dpia/templates', [DpiaController::class, 'templates'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::get('/dpia/mitigation', [DpiaController::class, 'mitigationTracker'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::get('/dpia/actions', [DpiaController::class, 'actions'])
            ->middleware('permission:'.CompliancePermission::VIEW);

        Route::get('/dpia/risks', [DpiaController::class, 'risks'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::post('/dpia/risks', [DpiaController::class, 'storeRisk'])
            ->middleware('permission:'.CompliancePermission::CREATE);
        Route::get('/dpia/risks/{risk}', [DpiaController::class, 'showRisk'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::put('/dpia/risks/{risk}', [DpiaController::class, 'updateRisk'])
            ->middleware('permission:'.CompliancePermission::UPDATE);
        Route::delete('/dpia/risks/{risk}', [DpiaController::class, 'destroyRisk'])
            ->middleware('permission:'.CompliancePermission::DELETE);
        Route::post('/dpia/risks/{risk}/assess', [DpiaController::class, 'assessRisk'])
            ->middleware('permission:'.CompliancePermission::UPDATE);
        Route::post('/dpia/risks/{risk}/actions', [DpiaController::class, 'storeRiskAction'])
            ->middleware('permission:'.CompliancePermission::UPDATE);
        Route::post('/dpia/risks/{risk}/actions/{action}/complete', [DpiaController::class, 'completeRiskAction'])
            ->middleware('permission:'.CompliancePermission::UPDATE);

        Route::prefix('dpia')->group(function (): void {
            Route::get('/', [DpiaController::class, 'index'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/', [DpiaController::class, 'store'])
                ->middleware('permission:'.CompliancePermission::CREATE);
            Route::get('/{assessment}', [DpiaController::class, 'show'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::put('/{assessment}', [DpiaController::class, 'update'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::delete('/{assessment}', [DpiaController::class, 'destroy'])
                ->middleware('permission:'.CompliancePermission::DELETE);
            Route::post('/{assessment}/wizard', [DpiaController::class, 'saveWizard'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{assessment}/submit', [DpiaController::class, 'submit'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{assessment}/approve', [DpiaController::class, 'approve'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{assessment}/reject', [DpiaController::class, 'reject'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
        });

        Route::get('/policies/dashboard', [PolicyDocumentController::class, 'dashboard'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::get('/policies/approvals', [PolicyDocumentController::class, 'approvalQueue'])
            ->middleware('permission:'.CompliancePermission::VIEW);
        Route::post('/policies/approvals/{approval}/approve', [PolicyDocumentController::class, 'approve'])
            ->middleware('permission:'.CompliancePermission::UPDATE);
        Route::post('/policies/approvals/{approval}/reject', [PolicyDocumentController::class, 'reject'])
            ->middleware('permission:'.CompliancePermission::UPDATE);

        Route::prefix('policies')->group(function (): void {
            Route::get('/', [PolicyDocumentController::class, 'index'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/', [PolicyDocumentController::class, 'store'])
                ->middleware('permission:'.CompliancePermission::CREATE);
            Route::get('/{policy}', [PolicyDocumentController::class, 'show'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::put('/{policy}', [PolicyDocumentController::class, 'update'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::delete('/{policy}', [PolicyDocumentController::class, 'destroy'])
                ->middleware('permission:'.CompliancePermission::DELETE);
            Route::get('/{policy}/versions', [PolicyDocumentController::class, 'versions'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::get('/{policy}/versions/compare', [PolicyDocumentController::class, 'compare'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::get('/{policy}/versions/{version}', [PolicyDocumentController::class, 'showVersion'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/{policy}/versions/{version}/restore', [PolicyDocumentController::class, 'restoreVersion'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{policy}/submit', [PolicyDocumentController::class, 'submit'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::post('/{policy}/publish', [PolicyDocumentController::class, 'publish'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
            Route::get('/{policy}/cms-versions', [PolicyDocumentController::class, 'cmsVersions'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::post('/{policy}/link-cms', [PolicyDocumentController::class, 'linkCms'])
                ->middleware('permission:'.CompliancePermission::UPDATE);
        });

        Route::prefix('analytics')->group(function (): void {
            Route::get('/dashboard', [ComplianceAnalyticsController::class, 'dashboard'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::get('/risks', [ComplianceAnalyticsController::class, 'risks'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::get('/reports/gdpr', [ComplianceAnalyticsController::class, 'gdprReport'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::get('/reports/consent', [ComplianceAnalyticsController::class, 'consentReport'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::get('/reports/audit', [ComplianceAnalyticsController::class, 'auditReport'])
                ->middleware('permission:'.CompliancePermission::VIEW);
            Route::get('/export', [ComplianceAnalyticsController::class, 'export'])
                ->middleware('permission:'.CompliancePermission::VIEW);
        });
    });
});
