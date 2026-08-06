<?php

use App\Domains\Content\Controllers\CmsApiKeyController;
use App\Domains\Content\Controllers\CmsSeoController;
use App\Domains\Content\Controllers\ContentCatalogController;
use App\Domains\Content\Controllers\ContentCategoryController;
use App\Domains\Content\Controllers\ContentController;
use App\Domains\Content\Controllers\ContentTagController;
use App\Domains\Content\Controllers\ContentVersionController;
use App\Domains\Content\Controllers\ContentWorkflowController;
use App\Domains\Content\Controllers\MediaFolderController;
use App\Domains\Content\Controllers\MediaLibraryController;
use App\Domains\Content\Controllers\PrivateCmsController;
use App\Domains\Content\Controllers\PublicCmsController;
use App\Domains\Content\Enums\ContentPermission;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:api'])->prefix('cms/public')->group(function (): void {
    Route::get('/contents', [PublicCmsController::class, 'index']);
    Route::get('/contents/{content}/seo', [PublicCmsController::class, 'seo']);
    Route::get('/contents/{content}', [PublicCmsController::class, 'show']);
    Route::get('/search', [PublicCmsController::class, 'search']);
    Route::get('/featured', [PublicCmsController::class, 'featured']);
    Route::get('/latest', [PublicCmsController::class, 'latest']);
    Route::get('/popular', [PublicCmsController::class, 'popular']);
    Route::get('/categories', [PublicCmsController::class, 'categories']);
    Route::get('/categories/{category}', [PublicCmsController::class, 'category']);
    Route::get('/categories/{category}/contents', [PublicCmsController::class, 'categoryContents']);
    Route::get('/tags', [PublicCmsController::class, 'tags']);
    Route::get('/tags/{tag}', [PublicCmsController::class, 'tag']);
    Route::get('/tags/{tag}/contents', [PublicCmsController::class, 'tagContents']);
});

Route::middleware(['cms.private', 'throttle:api'])->prefix('cms/private')->group(function (): void {
    Route::get('/contents', [PrivateCmsController::class, 'index']);
    Route::get('/contents/{content}/seo', [PrivateCmsController::class, 'seo']);
    Route::get('/contents/{content}/preview', [PrivateCmsController::class, 'preview']);
    Route::get('/contents/{content}', [PrivateCmsController::class, 'show']);
    Route::get('/search', [PrivateCmsController::class, 'search']);
    Route::get('/featured', [PrivateCmsController::class, 'featured']);
    Route::get('/latest', [PrivateCmsController::class, 'latest']);
    Route::get('/popular', [PrivateCmsController::class, 'popular']);
    Route::get('/categories', [PrivateCmsController::class, 'categories']);
    Route::get('/categories/{category}/contents', [PrivateCmsController::class, 'categoryContents']);
    Route::get('/tags', [PrivateCmsController::class, 'tags']);
    Route::get('/tags/{tag}/contents', [PrivateCmsController::class, 'tagContents']);
});

Route::middleware(['throttle:api'])->prefix('cms/seo')->group(function (): void {
    Route::get('/sitemap.json', [CmsSeoController::class, 'sitemapJson']);
    Route::get('/robots.json', [CmsSeoController::class, 'robotsJson']);
});

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('content')->group(function (): void {
    Route::get('/dashboard', [ContentController::class, 'dashboard'])
        ->middleware('permission:'.ContentPermission::VIEW);

    Route::get('/api-keys', [CmsApiKeyController::class, 'index'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/api-keys', [CmsApiKeyController::class, 'store'])
        ->middleware('permission:'.ContentPermission::CREATE);
    Route::delete('/api-keys/{apiKey}', [CmsApiKeyController::class, 'destroy'])
        ->middleware('permission:'.ContentPermission::DELETE);

    Route::get('/types', [ContentCatalogController::class, 'types'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/types', [ContentCatalogController::class, 'storeType'])
        ->middleware('permission:'.ContentPermission::CREATE);
    Route::put('/types/{type}', [ContentCatalogController::class, 'updateType'])
        ->middleware('permission:'.ContentPermission::UPDATE);

    Route::get('/statuses', [ContentCatalogController::class, 'statuses'])
        ->middleware('permission:'.ContentPermission::VIEW);

    Route::get('/categories/tree', [ContentCategoryController::class, 'tree'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/categories/bulk', [ContentCategoryController::class, 'bulk'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::get('/categories', [ContentCategoryController::class, 'index'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/categories', [ContentCategoryController::class, 'store'])
        ->middleware('permission:'.ContentPermission::CREATE);
    Route::get('/categories/{category}', [ContentCategoryController::class, 'show'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::put('/categories/{category}', [ContentCategoryController::class, 'update'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::delete('/categories/{category}', [ContentCategoryController::class, 'destroy'])
        ->middleware('permission:'.ContentPermission::DELETE);
    Route::post('/categories/{category}/restore', [ContentCategoryController::class, 'restore'])
        ->middleware('permission:'.ContentPermission::DELETE);

    Route::post('/tags/bulk', [ContentTagController::class, 'bulk'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::get('/tags', [ContentTagController::class, 'index'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/tags', [ContentTagController::class, 'store'])
        ->middleware('permission:'.ContentPermission::CREATE);
    Route::get('/tags/{tag}', [ContentTagController::class, 'show'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::put('/tags/{tag}', [ContentTagController::class, 'update'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::delete('/tags/{tag}', [ContentTagController::class, 'destroy'])
        ->middleware('permission:'.ContentPermission::DELETE);
    Route::post('/tags/{tag}/restore', [ContentTagController::class, 'restore'])
        ->middleware('permission:'.ContentPermission::DELETE);

    Route::get('/media-folders/tree', [MediaFolderController::class, 'tree'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::get('/media-folders', [MediaFolderController::class, 'index'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/media-folders', [MediaFolderController::class, 'store'])
        ->middleware('permission:'.ContentPermission::CREATE);
    Route::get('/media-folders/{folder}', [MediaFolderController::class, 'show'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::put('/media-folders/{folder}', [MediaFolderController::class, 'update'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::delete('/media-folders/{folder}', [MediaFolderController::class, 'destroy'])
        ->middleware('permission:'.ContentPermission::DELETE);
    Route::post('/media-folders/{folder}/restore', [MediaFolderController::class, 'restore'])
        ->middleware('permission:'.ContentPermission::DELETE);

    Route::get('/media-library', [MediaLibraryController::class, 'index'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/media-library', [MediaLibraryController::class, 'store'])
        ->middleware('permission:'.ContentPermission::CREATE);
    Route::get('/media-library/{media}/versions', [MediaLibraryController::class, 'versions'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/media-library/{media}/versions/{version}/restore', [MediaLibraryController::class, 'restoreVersion'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::post('/media-library/{media}/replace', [MediaLibraryController::class, 'replace'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::get('/media-library/{media}/download', [MediaLibraryController::class, 'download'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/media-library/{media}/restore', [MediaLibraryController::class, 'restore'])
        ->middleware('permission:'.ContentPermission::DELETE);
    Route::get('/media-library/{media}', [MediaLibraryController::class, 'show'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::put('/media-library/{media}', [MediaLibraryController::class, 'update'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::delete('/media-library/{media}', [MediaLibraryController::class, 'destroy'])
        ->middleware('permission:'.ContentPermission::DELETE);

    Route::get('/', [ContentController::class, 'index'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/', [ContentController::class, 'store'])
        ->middleware('permission:'.ContentPermission::CREATE);
    Route::post('/media', [ContentController::class, 'uploadMedia'])
        ->middleware('permission:'.ContentPermission::CREATE);

    Route::get('/workflow/queue', [ContentWorkflowController::class, 'queue'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::get('/{content}/workflow/history', [ContentWorkflowController::class, 'history'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/{content}/workflow/submit', [ContentWorkflowController::class, 'submit'])
        ->middleware('permission:'.ContentPermission::SUBMIT);
    Route::post('/{content}/workflow/review', [ContentWorkflowController::class, 'review'])
        ->middleware('permission:'.ContentPermission::REVIEW);
    Route::post('/{content}/workflow/approve', [ContentWorkflowController::class, 'approve'])
        ->middleware('permission:'.ContentPermission::APPROVE);
    Route::post('/{content}/workflow/reject', [ContentWorkflowController::class, 'reject'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/{content}/workflow/publish', [ContentWorkflowController::class, 'publish'])
        ->middleware('permission:'.ContentPermission::PUBLISH);
    Route::post('/{content}/workflow/archive', [ContentWorkflowController::class, 'archive'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::post('/{content}/workflow/return-to-draft', [ContentWorkflowController::class, 'returnToDraft'])
        ->middleware('permission:'.ContentPermission::UPDATE);

    Route::get('/{content}/versions/compare', [ContentVersionController::class, 'compare'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::get('/{content}/versions', [ContentVersionController::class, 'index'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::get('/{content}/versions/{version}', [ContentVersionController::class, 'show'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::post('/{content}/versions/{version}/restore', [ContentVersionController::class, 'restore'])
        ->middleware('permission:'.ContentPermission::UPDATE);

    Route::get('/{content}', [ContentController::class, 'show'])
        ->middleware('permission:'.ContentPermission::VIEW);
    Route::put('/{content}', [ContentController::class, 'update'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::post('/{content}/autosave', [ContentController::class, 'autosave'])
        ->middleware('permission:'.ContentPermission::UPDATE);
    Route::delete('/{content}', [ContentController::class, 'destroy'])
        ->middleware('permission:'.ContentPermission::DELETE);
    Route::post('/{content}/restore', [ContentController::class, 'restore'])
        ->middleware('permission:'.ContentPermission::DELETE);
    Route::post('/{content}/publish', [ContentController::class, 'publish'])
        ->middleware('permission:'.ContentPermission::PUBLISH);
    Route::post('/{content}/unpublish', [ContentController::class, 'unpublish'])
        ->middleware('permission:'.ContentPermission::PUBLISH);
});
