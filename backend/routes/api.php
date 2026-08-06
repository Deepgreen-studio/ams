<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Base API entry point. Versioned routes live under /api/v1.
| Do not register business endpoints here during Phase 1.1.
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Enterprise Application Management System API',
        'data' => [
            'name' => config('app.name'),
            'version' => 'v1',
        ],
    ]);
});
