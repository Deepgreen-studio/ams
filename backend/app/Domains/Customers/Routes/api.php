<?php

use App\Domains\Customers\Controllers\CustomerAnalyticsController;
use App\Domains\Customers\Controllers\CustomerApplicationController;
use App\Domains\Customers\Controllers\CustomerCommunicationCenterController;
use App\Domains\Customers\Controllers\CustomerCommunicationController;
use App\Domains\Customers\Controllers\CustomerContactController;
use App\Domains\Customers\Controllers\CustomerController;
use App\Domains\Customers\Controllers\CustomerDocumentController;
use App\Domains\Customers\Controllers\CustomerNoteController;
use App\Domains\Customers\Controllers\CustomerTaskController;
use App\Domains\Customers\Controllers\LicenseController;
use App\Domains\Customers\Controllers\SubscriptionController;
use App\Domains\Customers\Enums\CustomerAnalyticsPermission;
use App\Domains\Customers\Enums\CustomerApplicationPermission;
use App\Domains\Customers\Enums\CustomerCommunicationPermission;
use App\Domains\Customers\Enums\CustomerContactPermission;
use App\Domains\Customers\Enums\CustomerDocumentPermission;
use App\Domains\Customers\Enums\CustomerLicensePermission;
use App\Domains\Customers\Enums\CustomerPermission;
use App\Domains\Customers\Enums\CustomerSubscriptionPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('customers')->group(function (): void {
        Route::get('/', [CustomerController::class, 'index'])
            ->middleware('permission:' . CustomerPermission::VIEW . '|' . CustomerPermission::VIEW_TRASH);
        Route::get('/statistics', [CustomerController::class, 'statistics'])
            ->middleware('permission:' . CustomerPermission::VIEW);
        Route::get('/industries', [CustomerController::class, 'industries'])
            ->middleware('permission:' . CustomerPermission::VIEW);
        Route::post('/', [CustomerController::class, 'store'])
            ->middleware('permission:' . CustomerPermission::CREATE);
        Route::get('/{customer}/console', [CustomerController::class, 'console'])
            ->middleware('permission:' . CustomerPermission::VIEW);
        Route::post('/{customer}/anonymize', [CustomerController::class, 'anonymize'])
            ->middleware('permission:' . CustomerPermission::ANONYMIZE);
        Route::get('/{customer}', [CustomerController::class, 'show'])
            ->middleware('permission:' . CustomerPermission::VIEW);
        Route::put('/{customer}', [CustomerController::class, 'update'])
            ->middleware('permission:' . CustomerPermission::UPDATE);
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])
            ->middleware('permission:' . CustomerPermission::DELETE);
        Route::post('/{customer}/restore', [CustomerController::class, 'restore'])
            ->middleware('permission:' . CustomerPermission::RESTORE);
        Route::delete('/{customer}/force-delete', [CustomerController::class, 'forceDelete'])
            ->middleware('permission:' . CustomerPermission::FORCE_DELETE);
    });

    Route::prefix('customer-contacts')->group(function (): void {
        Route::get('/', [CustomerContactController::class, 'index'])
            ->middleware('permission:' . CustomerContactPermission::VIEW);
        Route::get('/export', [CustomerContactController::class, 'export'])
            ->middleware('permission:' . CustomerContactPermission::EXPORT);
        Route::post('/import', [CustomerContactController::class, 'import'])
            ->middleware('permission:' . CustomerContactPermission::IMPORT);
        Route::post('/', [CustomerContactController::class, 'store'])
            ->middleware('permission:' . CustomerContactPermission::CREATE);
        Route::get('/{contact}', [CustomerContactController::class, 'show'])
            ->middleware('permission:' . CustomerContactPermission::VIEW);
        Route::put('/{contact}', [CustomerContactController::class, 'update'])
            ->middleware('permission:' . CustomerContactPermission::UPDATE);
        Route::delete('/{contact}', [CustomerContactController::class, 'destroy'])
            ->middleware('permission:' . CustomerContactPermission::DELETE);
        Route::post('/{contact}/restore', [CustomerContactController::class, 'restore'])
            ->middleware('permission:' . CustomerContactPermission::RESTORE);
        Route::get('/{contact}/timeline', [CustomerContactController::class, 'timeline'])
            ->middleware('permission:' . CustomerContactPermission::VIEW);
    });

    Route::prefix('customer-applications')->group(function (): void {
        Route::get('/', [CustomerApplicationController::class, 'index'])
            ->middleware('permission:' . CustomerApplicationPermission::VIEW);
        Route::get('/history', [CustomerApplicationController::class, 'history'])
            ->middleware('permission:' . CustomerApplicationPermission::VIEW);
        Route::post('/', [CustomerApplicationController::class, 'store'])
            ->middleware('permission:' . CustomerApplicationPermission::CREATE);
        Route::get('/{assignment}', [CustomerApplicationController::class, 'show'])
            ->middleware('permission:' . CustomerApplicationPermission::VIEW);
        Route::put('/{assignment}', [CustomerApplicationController::class, 'update'])
            ->middleware('permission:' . CustomerApplicationPermission::UPDATE);
        Route::delete('/{assignment}', [CustomerApplicationController::class, 'destroy'])
            ->middleware('permission:' . CustomerApplicationPermission::DELETE);
        Route::post('/{assignment}/restore', [CustomerApplicationController::class, 'restore'])
            ->middleware('permission:' . CustomerApplicationPermission::RESTORE);
        Route::get('/{assignment}/timeline', [CustomerApplicationController::class, 'timeline'])
            ->middleware('permission:' . CustomerApplicationPermission::VIEW);
    });

    Route::prefix('customer-subscriptions')->group(function (): void {
        Route::get('/dashboard', [SubscriptionController::class, 'dashboard'])
            ->middleware('permission:' . CustomerSubscriptionPermission::VIEW);
        Route::get('/', [SubscriptionController::class, 'index'])
            ->middleware('permission:' . CustomerSubscriptionPermission::VIEW);
        Route::get('/statistics', [SubscriptionController::class, 'statistics'])
            ->middleware('permission:' . CustomerSubscriptionPermission::VIEW);
        Route::post('/', [SubscriptionController::class, 'store'])
            ->middleware('permission:' . CustomerSubscriptionPermission::CREATE);
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])
            ->middleware('permission:' . CustomerSubscriptionPermission::VIEW);
        Route::put('/{subscription}', [SubscriptionController::class, 'update'])
            ->middleware('permission:' . CustomerSubscriptionPermission::UPDATE);
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])
            ->middleware('permission:' . CustomerSubscriptionPermission::CANCEL);
        Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])
            ->middleware('permission:' . CustomerSubscriptionPermission::DELETE);
        Route::post('/{subscription}/restore', [SubscriptionController::class, 'restore'])
            ->middleware('permission:' . CustomerSubscriptionPermission::RESTORE);
        Route::get('/{subscription}/timeline', [SubscriptionController::class, 'timeline'])
            ->middleware('permission:' . CustomerSubscriptionPermission::VIEW);
    });

    Route::prefix('customer-licenses')->group(function (): void {
        Route::get('/', [LicenseController::class, 'index'])
            ->middleware('permission:' . CustomerLicensePermission::VIEW);
        Route::get('/history', [LicenseController::class, 'history'])
            ->middleware('permission:' . CustomerLicensePermission::VIEW);
        Route::post('/', [LicenseController::class, 'store'])
            ->middleware('permission:' . CustomerLicensePermission::CREATE);
        Route::get('/{license}', [LicenseController::class, 'show'])
            ->middleware('permission:' . CustomerLicensePermission::VIEW);
        Route::put('/{license}', [LicenseController::class, 'update'])
            ->middleware('permission:' . CustomerLicensePermission::UPDATE);
        Route::post('/{license}/revoke', [LicenseController::class, 'revoke'])
            ->middleware('permission:' . CustomerLicensePermission::REVOKE);
        Route::delete('/{license}', [LicenseController::class, 'destroy'])
            ->middleware('permission:' . CustomerLicensePermission::DELETE);
        Route::post('/{license}/restore', [LicenseController::class, 'restore'])
            ->middleware('permission:' . CustomerLicensePermission::RESTORE);
        Route::get('/{license}/timeline', [LicenseController::class, 'timeline'])
            ->middleware('permission:' . CustomerLicensePermission::VIEW);
    });

    Route::prefix('customer-documents')->group(function (): void {
        Route::get('/', [CustomerDocumentController::class, 'index'])
            ->middleware('permission:' . CustomerDocumentPermission::VIEW);
        Route::get('/folders', [CustomerDocumentController::class, 'folders'])
            ->middleware('permission:' . CustomerDocumentPermission::VIEW);
        Route::get('/statistics', [CustomerDocumentController::class, 'statistics'])
            ->middleware('permission:' . CustomerDocumentPermission::VIEW);
        Route::post('/', [CustomerDocumentController::class, 'store'])
            ->middleware('permission:' . CustomerDocumentPermission::CREATE);
        Route::get('/{document}', [CustomerDocumentController::class, 'show'])
            ->middleware('permission:' . CustomerDocumentPermission::VIEW);
        Route::put('/{document}', [CustomerDocumentController::class, 'update'])
            ->middleware('permission:' . CustomerDocumentPermission::UPDATE);
        Route::post('/{document}/versions', [CustomerDocumentController::class, 'uploadVersion'])
            ->middleware('permission:' . CustomerDocumentPermission::UPDATE);
        Route::get('/{document}/versions', [CustomerDocumentController::class, 'versions'])
            ->middleware('permission:' . CustomerDocumentPermission::VIEW);
        Route::get('/{document}/download', [CustomerDocumentController::class, 'download'])
            ->middleware('permission:' . CustomerDocumentPermission::DOWNLOAD);
        Route::get('/{document}/preview', [CustomerDocumentController::class, 'preview'])
            ->middleware('permission:' . CustomerDocumentPermission::VIEW);
        Route::delete('/{document}', [CustomerDocumentController::class, 'destroy'])
            ->middleware('permission:' . CustomerDocumentPermission::DELETE);
        Route::post('/{document}/restore', [CustomerDocumentController::class, 'restore'])
            ->middleware('permission:' . CustomerDocumentPermission::RESTORE);
        Route::get('/{document}/timeline', [CustomerDocumentController::class, 'timeline'])
            ->middleware('permission:' . CustomerDocumentPermission::VIEW);
    });

    Route::prefix('customer-communication-center')->group(function (): void {
        Route::get('/overview', [CustomerCommunicationCenterController::class, 'overview'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
        Route::get('/timeline', [CustomerCommunicationCenterController::class, 'timeline'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
        Route::get('/activity', [CustomerCommunicationCenterController::class, 'activity'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
        Route::get('/calendar', [CustomerCommunicationCenterController::class, 'calendar'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
    });

    Route::prefix('customer-notes')->group(function (): void {
        Route::get('/', [CustomerNoteController::class, 'index'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
        Route::post('/', [CustomerNoteController::class, 'store'])
            ->middleware('permission:' . CustomerCommunicationPermission::CREATE);
        Route::get('/{note}', [CustomerNoteController::class, 'show'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
        Route::put('/{note}', [CustomerNoteController::class, 'update'])
            ->middleware('permission:' . CustomerCommunicationPermission::UPDATE);
        Route::delete('/{note}', [CustomerNoteController::class, 'destroy'])
            ->middleware('permission:' . CustomerCommunicationPermission::DELETE);
        Route::post('/{note}/restore', [CustomerNoteController::class, 'restore'])
            ->middleware('permission:' . CustomerCommunicationPermission::RESTORE);
    });

    Route::prefix('customer-tasks')->group(function (): void {
        Route::get('/', [CustomerTaskController::class, 'index'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
        Route::get('/calendar', [CustomerTaskController::class, 'calendar'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
        Route::post('/', [CustomerTaskController::class, 'store'])
            ->middleware('permission:' . CustomerCommunicationPermission::CREATE);
        Route::get('/{task}', [CustomerTaskController::class, 'show'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
        Route::put('/{task}', [CustomerTaskController::class, 'update'])
            ->middleware('permission:' . CustomerCommunicationPermission::UPDATE);
        Route::post('/{task}/complete', [CustomerTaskController::class, 'complete'])
            ->middleware('permission:' . CustomerCommunicationPermission::UPDATE);
        Route::delete('/{task}', [CustomerTaskController::class, 'destroy'])
            ->middleware('permission:' . CustomerCommunicationPermission::DELETE);
        Route::post('/{task}/restore', [CustomerTaskController::class, 'restore'])
            ->middleware('permission:' . CustomerCommunicationPermission::RESTORE);
    });

    Route::prefix('customer-communications')->group(function (): void {
        Route::get('/', [CustomerCommunicationController::class, 'index'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
        Route::post('/', [CustomerCommunicationController::class, 'store'])
            ->middleware('permission:' . CustomerCommunicationPermission::CREATE);
        Route::get('/{communication}', [CustomerCommunicationController::class, 'show'])
            ->middleware('permission:' . CustomerCommunicationPermission::VIEW);
        Route::put('/{communication}', [CustomerCommunicationController::class, 'update'])
            ->middleware('permission:' . CustomerCommunicationPermission::UPDATE);
        Route::delete('/{communication}', [CustomerCommunicationController::class, 'destroy'])
            ->middleware('permission:' . CustomerCommunicationPermission::DELETE);
        Route::post('/{communication}/restore', [CustomerCommunicationController::class, 'restore'])
            ->middleware('permission:' . CustomerCommunicationPermission::RESTORE);
    });

    Route::prefix('customer-analytics')->group(function (): void {
        Route::get('/dashboard', [CustomerAnalyticsController::class, 'dashboard'])
            ->middleware('permission:' . CustomerAnalyticsPermission::VIEW);
        Route::get('/health', [CustomerAnalyticsController::class, 'health'])
            ->middleware('permission:' . CustomerAnalyticsPermission::VIEW);
        Route::get('/trends', [CustomerAnalyticsController::class, 'trends'])
            ->middleware('permission:' . CustomerAnalyticsPermission::VIEW);
        Route::get('/usage', [CustomerAnalyticsController::class, 'usage'])
            ->middleware('permission:' . CustomerAnalyticsPermission::VIEW);
        Route::post('/refresh', [CustomerAnalyticsController::class, 'refresh'])
            ->middleware('permission:' . CustomerAnalyticsPermission::REFRESH);
    });
});
