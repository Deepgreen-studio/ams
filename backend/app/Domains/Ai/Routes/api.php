<?php

use App\Domains\Ai\Controllers\AiController;
use App\Domains\Ai\Enums\AiPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('ai')->group(function (): void {
    Route::get('/dashboard', [AiController::class, 'dashboard'])
        ->middleware('permission:'.AiPermission::VIEW);
    Route::get('/catalog', [AiController::class, 'catalog'])
        ->middleware('permission:'.AiPermission::VIEW);
    Route::get('/analytics', [AiController::class, 'analytics'])
        ->middleware('permission:'.AiPermission::VIEW);

    Route::get('/settings', [AiController::class, 'settings'])
        ->middleware('permission:'.AiPermission::MANAGE);
    Route::put('/settings', [AiController::class, 'updateSettings'])
        ->middleware('permission:'.AiPermission::MANAGE);

    Route::get('/providers', [AiController::class, 'providers'])
        ->middleware('permission:'.AiPermission::VIEW);
    Route::post('/providers', [AiController::class, 'storeProvider'])
        ->middleware('permission:'.AiPermission::CREATE);
    Route::get('/providers/{provider}', [AiController::class, 'showProvider'])
        ->middleware('permission:'.AiPermission::VIEW);
    Route::put('/providers/{provider}', [AiController::class, 'updateProvider'])
        ->middleware('permission:'.AiPermission::UPDATE);
    Route::delete('/providers/{provider}', [AiController::class, 'destroyProvider'])
        ->middleware('permission:'.AiPermission::DELETE);
    Route::post('/providers/{provider}/test', [AiController::class, 'testProvider'])
        ->middleware('permission:'.AiPermission::MANAGE);

    Route::get('/prompts', [AiController::class, 'prompts'])
        ->middleware('permission:'.AiPermission::VIEW);
    Route::post('/prompts', [AiController::class, 'storePrompt'])
        ->middleware('permission:'.AiPermission::CREATE);
    Route::get('/prompts/{prompt}', [AiController::class, 'showPrompt'])
        ->middleware('permission:'.AiPermission::VIEW);
    Route::put('/prompts/{prompt}', [AiController::class, 'updatePrompt'])
        ->middleware('permission:'.AiPermission::UPDATE);
    Route::delete('/prompts/{prompt}', [AiController::class, 'destroyPrompt'])
        ->middleware('permission:'.AiPermission::DELETE);
    Route::post('/prompts/{prompt}/publish', [AiController::class, 'publishPrompt'])
        ->middleware('permission:'.AiPermission::MANAGE);

    Route::get('/conversations', [AiController::class, 'conversations'])
        ->middleware('permission:'.AiPermission::VIEW);
    Route::get('/conversations/{conversation}', [AiController::class, 'showConversation'])
        ->middleware('permission:'.AiPermission::VIEW);
    Route::post('/conversations/{conversation}/archive', [AiController::class, 'archiveConversation'])
        ->middleware('permission:'.AiPermission::CHAT);

    Route::post('/chat', [AiController::class, 'chat'])
        ->middleware('permission:'.AiPermission::CHAT);

    Route::post('/features/suggest', [AiController::class, 'suggest'])
        ->middleware('permission:'.AiPermission::CHAT);
    Route::post('/features/categorize', [AiController::class, 'categorize'])
        ->middleware('permission:'.AiPermission::CHAT);
    Route::post('/features/route-ticket', [AiController::class, 'routeTicket'])
        ->middleware('permission:'.AiPermission::CHAT);
    Route::post('/features/content-suggestions', [AiController::class, 'contentSuggestions'])
        ->middleware('permission:'.AiPermission::CHAT);
    Route::post('/features/translate', [AiController::class, 'translate'])
        ->middleware('permission:'.AiPermission::CHAT);
    Route::post('/features/summarize', [AiController::class, 'summarize'])
        ->middleware('permission:'.AiPermission::CHAT);
    Route::post('/features/search', [AiController::class, 'search'])
        ->middleware('permission:'.AiPermission::CHAT);
    Route::post('/features/knowledge', [AiController::class, 'knowledge'])
        ->middleware('permission:'.AiPermission::CHAT);

    Route::get('/logs', [AiController::class, 'logs'])
        ->middleware('permission:'.AiPermission::VIEW);
    Route::get('/logs/{log}', [AiController::class, 'showLog'])
        ->middleware('permission:'.AiPermission::VIEW);
});
