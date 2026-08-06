<?php

use App\Domains\Content\Controllers\CmsSeoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Backend is API-first. The Vue SPA lives in /frontend and consumes /api/v1.
| SEO discovery endpoints are served here for crawlers.
|
*/

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'AMS API backend',
        'data' => [
            'docs' => url('/up'),
            'api' => url('/api/v1'),
            'cms_public' => url('/api/v1/cms/public'),
            'sitemap' => url('/sitemap.xml'),
            'robots' => url('/robots.txt'),
        ],
    ]);
});

Route::get('/sitemap.xml', [CmsSeoController::class, 'sitemap']);
Route::get('/robots.txt', [CmsSeoController::class, 'robots']);
