<?php

use App\Domains\Support\Controllers\SupportTicketController;
use App\Domains\Support\Controllers\SupportTicketConversationController;
use App\Domains\Support\Controllers\SupportSlaController;
use App\Domains\Support\Controllers\KnowledgeBaseController;
use App\Domains\Support\Controllers\SupportCannedResponseController;
use App\Domains\Support\Controllers\PortalSupportTicketController;
use App\Domains\Support\Enums\SupportPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('portal')->group(function (): void {
    Route::get('/me', [PortalSupportTicketController::class, 'profile']);
    Route::prefix('support/tickets')->group(function (): void {
        Route::get('/', [PortalSupportTicketController::class, 'index']);
        Route::post('/', [PortalSupportTicketController::class, 'store']);
        Route::get('/{ticket}', [PortalSupportTicketController::class, 'show']);
        Route::get('/{ticket}/messages', [PortalSupportTicketController::class, 'messages']);
        Route::post('/{ticket}/messages', [PortalSupportTicketController::class, 'reply']);
    });
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::prefix('support')->group(function (): void {
        Route::get('/dashboard', [SupportTicketController::class, 'dashboard'])
            ->middleware('permission:'.SupportPermission::VIEW);
        Route::get('/agents', [SupportTicketController::class, 'agents'])
            ->middleware('permission:'.SupportPermission::VIEW);

        Route::prefix('canned-responses')->group(function (): void {
            Route::get('/dashboard', [SupportCannedResponseController::class, 'dashboard'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::get('/', [SupportCannedResponseController::class, 'index'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/', [SupportCannedResponseController::class, 'store'])
                ->middleware('permission:'.SupportPermission::CREATE.'|'.SupportPermission::MANAGE);
            Route::get('/{response}', [SupportCannedResponseController::class, 'show'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::put('/{response}', [SupportCannedResponseController::class, 'update'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::delete('/{response}', [SupportCannedResponseController::class, 'destroy'])
                ->middleware('permission:'.SupportPermission::DELETE.'|'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/{response}/use', [SupportCannedResponseController::class, 'use'])
                ->middleware('permission:'.SupportPermission::VIEW);
        });

        Route::prefix('knowledge')->group(function (): void {
            Route::get('/dashboard', [KnowledgeBaseController::class, 'dashboard'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::get('/articles', [KnowledgeBaseController::class, 'index'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/articles', [KnowledgeBaseController::class, 'store'])
                ->middleware('permission:'.SupportPermission::CREATE.'|'.SupportPermission::MANAGE);
            Route::get('/articles/{article}', [KnowledgeBaseController::class, 'show'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::put('/articles/{article}', [KnowledgeBaseController::class, 'update'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/articles/{article}/publish', [KnowledgeBaseController::class, 'publish'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/articles/{article}/archive', [KnowledgeBaseController::class, 'archive'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::delete('/articles/{article}', [KnowledgeBaseController::class, 'destroy'])
                ->middleware('permission:'.SupportPermission::DELETE.'|'.SupportPermission::MANAGE);
            Route::post('/articles/{article}/link-cms', [KnowledgeBaseController::class, 'linkCms'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/articles/{article}/unlink-cms', [KnowledgeBaseController::class, 'unlinkCms'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::get('/articles/{article}/versions', [KnowledgeBaseController::class, 'versions'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/articles/{article}/versions/{version}/restore', [KnowledgeBaseController::class, 'restoreVersion'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/articles/{article}/feedback', [KnowledgeBaseController::class, 'feedback'])
                ->middleware('permission:'.SupportPermission::VIEW);

            Route::get('/categories', [KnowledgeBaseController::class, 'categories'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/categories', [KnowledgeBaseController::class, 'storeCategory'])
                ->middleware('permission:'.SupportPermission::MANAGE);
            Route::put('/categories/{category}', [KnowledgeBaseController::class, 'updateCategory'])
                ->middleware('permission:'.SupportPermission::MANAGE);
            Route::delete('/categories/{category}', [KnowledgeBaseController::class, 'destroyCategory'])
                ->middleware('permission:'.SupportPermission::MANAGE);

            Route::get('/tags', [KnowledgeBaseController::class, 'tags'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/tags', [KnowledgeBaseController::class, 'storeTag'])
                ->middleware('permission:'.SupportPermission::MANAGE);
            Route::put('/tags/{tag}', [KnowledgeBaseController::class, 'updateTag'])
                ->middleware('permission:'.SupportPermission::MANAGE);
            Route::delete('/tags/{tag}', [KnowledgeBaseController::class, 'destroyTag'])
                ->middleware('permission:'.SupportPermission::MANAGE);
        });

        Route::prefix('sla')->group(function (): void {
            Route::get('/dashboard', [SupportSlaController::class, 'dashboard'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::get('/escalations', [SupportSlaController::class, 'escalationQueue'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/escalations/{escalation}/acknowledge', [SupportSlaController::class, 'acknowledgeEscalation'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/escalations/{escalation}/resolve', [SupportSlaController::class, 'resolveEscalation'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::get('/violations', [SupportSlaController::class, 'violations'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/evaluate', [SupportSlaController::class, 'evaluate'])
                ->middleware('permission:'.SupportPermission::MANAGE);

            Route::get('/policies', [SupportSlaController::class, 'policies'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/policies', [SupportSlaController::class, 'storePolicy'])
                ->middleware('permission:'.SupportPermission::MANAGE);
            Route::get('/policies/{policy}', [SupportSlaController::class, 'showPolicy'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::put('/policies/{policy}', [SupportSlaController::class, 'updatePolicy'])
                ->middleware('permission:'.SupportPermission::MANAGE);
            Route::delete('/policies/{policy}', [SupportSlaController::class, 'destroyPolicy'])
                ->middleware('permission:'.SupportPermission::MANAGE);

            Route::get('/calendars', [SupportSlaController::class, 'calendars'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/calendars', [SupportSlaController::class, 'storeCalendar'])
                ->middleware('permission:'.SupportPermission::MANAGE);
            Route::put('/calendars/{calendar}', [SupportSlaController::class, 'updateCalendar'])
                ->middleware('permission:'.SupportPermission::MANAGE);

            Route::get('/holidays', [SupportSlaController::class, 'holidays'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/holidays', [SupportSlaController::class, 'storeHoliday'])
                ->middleware('permission:'.SupportPermission::MANAGE);
            Route::put('/holidays/{holiday}', [SupportSlaController::class, 'updateHoliday'])
                ->middleware('permission:'.SupportPermission::MANAGE);
            Route::delete('/holidays/{holiday}', [SupportSlaController::class, 'destroyHoliday'])
                ->middleware('permission:'.SupportPermission::MANAGE);
        });

        Route::prefix('tickets')->group(function (): void {
            Route::get('/', [SupportTicketController::class, 'index'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::get('/board', [SupportTicketController::class, 'board'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::get('/queue', [SupportTicketController::class, 'queue'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/', [SupportTicketController::class, 'store'])
                ->middleware('permission:'.SupportPermission::CREATE);
            Route::get('/{ticket}', [SupportTicketController::class, 'show'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::get('/{ticket}/timeline', [SupportTicketController::class, 'timeline'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::get('/{ticket}/messages', [SupportTicketConversationController::class, 'index'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::post('/{ticket}/messages', [SupportTicketConversationController::class, 'store'])
                ->middleware('permission:'.SupportPermission::CREATE.'|'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/{ticket}/messages/read', [SupportTicketConversationController::class, 'markRead'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::delete('/{ticket}/messages/{message}', [SupportTicketConversationController::class, 'destroy'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/{ticket}/attachments', [SupportTicketConversationController::class, 'storeAttachments'])
                ->middleware('permission:'.SupportPermission::CREATE.'|'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::get('/{ticket}/attachments/{attachment}/download', [SupportTicketConversationController::class, 'download'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::get('/{ticket}/attachments/{attachment}/preview', [SupportTicketConversationController::class, 'preview'])
                ->middleware('permission:'.SupportPermission::VIEW);
            Route::delete('/{ticket}/attachments/{attachment}', [SupportTicketConversationController::class, 'destroyAttachment'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::put('/{ticket}', [SupportTicketController::class, 'update'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/{ticket}/transition', [SupportTicketController::class, 'transition'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/{ticket}/assign', [SupportTicketController::class, 'assign'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/{ticket}/close', [SupportTicketController::class, 'close'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::post('/{ticket}/reopen', [SupportTicketController::class, 'reopen'])
                ->middleware('permission:'.SupportPermission::UPDATE.'|'.SupportPermission::MANAGE);
            Route::delete('/{ticket}', [SupportTicketController::class, 'destroy'])
                ->middleware('permission:'.SupportPermission::DELETE.'|'.SupportPermission::MANAGE);
            Route::post('/{ticket}/restore', [SupportTicketController::class, 'restore'])
                ->middleware('permission:'.SupportPermission::DELETE.'|'.SupportPermission::MANAGE);
        });
    });
});
