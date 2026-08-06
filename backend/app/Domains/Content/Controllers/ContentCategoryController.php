<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Enums\ContentPermission;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Requests\BulkContentCategoryRequest;
use App\Domains\Content\Requests\StoreContentCategoryRequest;
use App\Domains\Content\Requests\UpdateContentCategoryRequest;
use App\Domains\Content\Resources\ContentCategoryCollection;
use App\Domains\Content\Resources\ContentCategoryResource;
use App\Domains\Content\Services\ContentCategoryService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentCategoryController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContentCategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        $categories = $this->categoryService->list($request->only([
            'search',
            'status',
            'is_active',
            'parent_id',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'categories' => (new ContentCategoryCollection($categories))->resolve(),
        ]);
    }

    public function tree(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        return ApiResponse::success([
            'tree' => $this->categoryService->tree($request->only(['status', 'is_active', 'search', 'trashed'])),
        ]);
    }

    public function store(StoreContentCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Content::class);

        /** @var User $actor */
        $actor = $request->user();
        $category = $this->categoryService->create($request->validated(), $actor);

        return ApiResponse::success([
            'category' => new ContentCategoryResource($category),
        ], 'Content category created successfully.', 201);
    }

    public function show(string $category): JsonResponse
    {
        $this->authorize('viewAny', Content::class);
        $model = $this->categoryService->show($category);

        return ApiResponse::success([
            'category' => new ContentCategoryResource($model),
        ]);
    }

    public function update(UpdateContentCategoryRequest $request, string $category): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::UPDATE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->categoryService->update($category, $request->validated(), $actor);

        return ApiResponse::success([
            'category' => new ContentCategoryResource($updated),
        ], 'Content category updated successfully.');
    }

    public function destroy(Request $request, string $category): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::DELETE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $this->categoryService->delete($category, $actor);

        return ApiResponse::success(null, 'Content category deleted successfully.');
    }

    public function restore(Request $request, string $category): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::DELETE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->categoryService->restore($category, $actor);

        return ApiResponse::success([
            'category' => new ContentCategoryResource($restored),
        ], 'Content category restored successfully.');
    }

    public function bulk(BulkContentCategoryRequest $request): JsonResponse
    {
        $action = (string) $request->validated('action');
        $permission = in_array($action, ['delete', 'restore'], true)
            ? ContentPermission::DELETE
            : ContentPermission::UPDATE;
        abort_unless($request->user()?->can($permission), 403);

        /** @var User $actor */
        $actor = $request->user();
        $result = $this->categoryService->bulk($request->validated(), $actor);

        return ApiResponse::success($result, 'Bulk category action completed.');
    }
}
