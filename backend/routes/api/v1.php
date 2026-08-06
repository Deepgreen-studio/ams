<?php

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Domain route files are auto-loaded by DomainServiceProvider.
| Keep only cross-cutting health checks here.
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API v1 is healthy',
        'data' => [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ],
    ]);
});
