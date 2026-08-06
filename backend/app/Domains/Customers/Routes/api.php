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
use App\Domains\Customers\Enums\CustomerPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('customers')->group(function (): void {
        Route::get('/', [CustomerController::class, 'index'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/statistics', [CustomerController::class, 'statistics'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::post('/', [CustomerController::class, 'store'])
            ->middleware('permission:'.CustomerPermission::CREATE);
        Route::get('/{customer}', [CustomerController::class, 'show'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::put('/{customer}', [CustomerController::class, 'update'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])
            ->middleware('permission:'.CustomerPermission::DELETE);
        Route::post('/{customer}/restore', [CustomerController::class, 'restore'])
            ->middleware('permission:'.CustomerPermission::RESTORE.'|'.CustomerPermission::DELETE);
    });

    Route::prefix('customer-contacts')->group(function (): void {
        Route::get('/', [CustomerContactController::class, 'index'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::post('/', [CustomerContactController::class, 'store'])
            ->middleware('permission:'.CustomerPermission::UPDATE.'|'.CustomerPermission::CREATE);
        Route::get('/{contact}', [CustomerContactController::class, 'show'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::put('/{contact}', [CustomerContactController::class, 'update'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::delete('/{contact}', [CustomerContactController::class, 'destroy'])
            ->middleware('permission:'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::post('/{contact}/restore', [CustomerContactController::class, 'restore'])
            ->middleware('permission:'.CustomerPermission::RESTORE.'|'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::get('/{contact}/timeline', [CustomerContactController::class, 'timeline'])
            ->middleware('permission:'.CustomerPermission::VIEW);
    });

    Route::prefix('customer-applications')->group(function (): void {
        Route::get('/', [CustomerApplicationController::class, 'index'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/history', [CustomerApplicationController::class, 'history'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::post('/', [CustomerApplicationController::class, 'store'])
            ->middleware('permission:'.CustomerPermission::UPDATE.'|'.CustomerPermission::CREATE);
        Route::get('/{assignment}', [CustomerApplicationController::class, 'show'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::put('/{assignment}', [CustomerApplicationController::class, 'update'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::delete('/{assignment}', [CustomerApplicationController::class, 'destroy'])
            ->middleware('permission:'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::post('/{assignment}/restore', [CustomerApplicationController::class, 'restore'])
            ->middleware('permission:'.CustomerPermission::RESTORE.'|'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::get('/{assignment}/timeline', [CustomerApplicationController::class, 'timeline'])
            ->middleware('permission:'.CustomerPermission::VIEW);
    });

    Route::prefix('customer-subscriptions')->group(function (): void {
        Route::get('/dashboard', [SubscriptionController::class, 'dashboard'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/', [SubscriptionController::class, 'index'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/statistics', [SubscriptionController::class, 'statistics'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::post('/', [SubscriptionController::class, 'store'])
            ->middleware('permission:'.CustomerPermission::UPDATE.'|'.CustomerPermission::CREATE);
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::put('/{subscription}', [SubscriptionController::class, 'update'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])
            ->middleware('permission:'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::post('/{subscription}/restore', [SubscriptionController::class, 'restore'])
            ->middleware('permission:'.CustomerPermission::RESTORE.'|'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::get('/{subscription}/timeline', [SubscriptionController::class, 'timeline'])
            ->middleware('permission:'.CustomerPermission::VIEW);
    });

    Route::prefix('customer-licenses')->group(function (): void {
        Route::get('/', [LicenseController::class, 'index'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/history', [LicenseController::class, 'history'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::post('/', [LicenseController::class, 'store'])
            ->middleware('permission:'.CustomerPermission::UPDATE.'|'.CustomerPermission::CREATE);
        Route::get('/{license}', [LicenseController::class, 'show'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::put('/{license}', [LicenseController::class, 'update'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::post('/{license}/revoke', [LicenseController::class, 'revoke'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::delete('/{license}', [LicenseController::class, 'destroy'])
            ->middleware('permission:'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::post('/{license}/restore', [LicenseController::class, 'restore'])
            ->middleware('permission:'.CustomerPermission::RESTORE.'|'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::get('/{license}/timeline', [LicenseController::class, 'timeline'])
            ->middleware('permission:'.CustomerPermission::VIEW);
    });

    Route::prefix('customer-documents')->group(function (): void {
        Route::get('/', [CustomerDocumentController::class, 'index'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/folders', [CustomerDocumentController::class, 'folders'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/statistics', [CustomerDocumentController::class, 'statistics'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::post('/', [CustomerDocumentController::class, 'store'])
            ->middleware('permission:'.CustomerPermission::UPDATE.'|'.CustomerPermission::CREATE);
        Route::get('/{document}', [CustomerDocumentController::class, 'show'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::put('/{document}', [CustomerDocumentController::class, 'update'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::post('/{document}/versions', [CustomerDocumentController::class, 'uploadVersion'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::get('/{document}/versions', [CustomerDocumentController::class, 'versions'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/{document}/download', [CustomerDocumentController::class, 'download'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/{document}/preview', [CustomerDocumentController::class, 'preview'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::delete('/{document}', [CustomerDocumentController::class, 'destroy'])
            ->middleware('permission:'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::post('/{document}/restore', [CustomerDocumentController::class, 'restore'])
            ->middleware('permission:'.CustomerPermission::RESTORE.'|'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::get('/{document}/timeline', [CustomerDocumentController::class, 'timeline'])
            ->middleware('permission:'.CustomerPermission::VIEW);
    });

    Route::prefix('customer-communication-center')->group(function (): void {
        Route::get('/overview', [CustomerCommunicationCenterController::class, 'overview'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/timeline', [CustomerCommunicationCenterController::class, 'timeline'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/activity', [CustomerCommunicationCenterController::class, 'activity'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/calendar', [CustomerCommunicationCenterController::class, 'calendar'])
            ->middleware('permission:'.CustomerPermission::VIEW);
    });

    Route::prefix('customer-notes')->group(function (): void {
        Route::get('/', [CustomerNoteController::class, 'index'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::post('/', [CustomerNoteController::class, 'store'])
            ->middleware('permission:'.CustomerPermission::UPDATE.'|'.CustomerPermission::CREATE);
        Route::get('/{note}', [CustomerNoteController::class, 'show'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::put('/{note}', [CustomerNoteController::class, 'update'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::delete('/{note}', [CustomerNoteController::class, 'destroy'])
            ->middleware('permission:'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::post('/{note}/restore', [CustomerNoteController::class, 'restore'])
            ->middleware('permission:'.CustomerPermission::RESTORE.'|'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
    });

    Route::prefix('customer-tasks')->group(function (): void {
        Route::get('/', [CustomerTaskController::class, 'index'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/calendar', [CustomerTaskController::class, 'calendar'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::post('/', [CustomerTaskController::class, 'store'])
            ->middleware('permission:'.CustomerPermission::UPDATE.'|'.CustomerPermission::CREATE);
        Route::get('/{task}', [CustomerTaskController::class, 'show'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::put('/{task}', [CustomerTaskController::class, 'update'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::post('/{task}/complete', [CustomerTaskController::class, 'complete'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::delete('/{task}', [CustomerTaskController::class, 'destroy'])
            ->middleware('permission:'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::post('/{task}/restore', [CustomerTaskController::class, 'restore'])
            ->middleware('permission:'.CustomerPermission::RESTORE.'|'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
    });

    Route::prefix('customer-communications')->group(function (): void {
        Route::get('/', [CustomerCommunicationController::class, 'index'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::post('/', [CustomerCommunicationController::class, 'store'])
            ->middleware('permission:'.CustomerPermission::UPDATE.'|'.CustomerPermission::CREATE);
        Route::get('/{communication}', [CustomerCommunicationController::class, 'show'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::put('/{communication}', [CustomerCommunicationController::class, 'update'])
            ->middleware('permission:'.CustomerPermission::UPDATE);
        Route::delete('/{communication}', [CustomerCommunicationController::class, 'destroy'])
            ->middleware('permission:'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
        Route::post('/{communication}/restore', [CustomerCommunicationController::class, 'restore'])
            ->middleware('permission:'.CustomerPermission::RESTORE.'|'.CustomerPermission::DELETE.'|'.CustomerPermission::UPDATE);
    });

    Route::prefix('customer-analytics')->group(function (): void {
        Route::get('/dashboard', [CustomerAnalyticsController::class, 'dashboard'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/health', [CustomerAnalyticsController::class, 'health'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/trends', [CustomerAnalyticsController::class, 'trends'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::get('/usage', [CustomerAnalyticsController::class, 'usage'])
            ->middleware('permission:'.CustomerPermission::VIEW);
        Route::post('/refresh', [CustomerAnalyticsController::class, 'refresh'])
            ->middleware('permission:'.CustomerPermission::UPDATE.'|'.CustomerPermission::CREATE);
    });
});
