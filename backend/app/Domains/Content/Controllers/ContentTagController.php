<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Enums\ContentPermission;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Requests\BulkContentTagRequest;
use App\Domains\Content\Requests\StoreContentTagRequest;
use App\Domains\Content\Requests\UpdateContentTagRequest;
use App\Domains\Content\Resources\ContentTagCollection;
use App\Domains\Content\Resources\ContentTagResource;
use App\Domains\Content\Services\ContentTagService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentTagController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContentTagService $tagService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        $tags = $this->tagService->list($request->only([
            'search',
            'status',
            'is_active',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'tags' => (new ContentTagCollection($tags))->resolve(),
        ]);
    }

    public function store(StoreContentTagRequest $request): JsonResponse
    {
        $this->authorize('create', Content::class);

        /** @var User $actor */
        $actor = $request->user();
        $tag = $this->tagService->create($request->validated(), $actor);

        return ApiResponse::success([
            'tag' => new ContentTagResource($tag),
        ], 'Content tag created successfully.', 201);
    }

    public function show(string $tag): JsonResponse
    {
        $this->authorize('viewAny', Content::class);
        $model = $this->tagService->show($tag);

        return ApiResponse::success([
            'tag' => new ContentTagResource($model),
        ]);
    }

    public function update(UpdateContentTagRequest $request, string $tag): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::UPDATE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->tagService->update($tag, $request->validated(), $actor);

        return ApiResponse::success([
            'tag' => new ContentTagResource($updated),
        ], 'Content tag updated successfully.');
    }

    public function destroy(Request $request, string $tag): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::DELETE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $this->tagService->delete($tag, $actor);

        return ApiResponse::success(null, 'Content tag deleted successfully.');
    }

    public function restore(Request $request, string $tag): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::DELETE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->tagService->restore($tag, $actor);

        return ApiResponse::success([
            'tag' => new ContentTagResource($restored),
        ], 'Content tag restored successfully.');
    }

    public function bulk(BulkContentTagRequest $request): JsonResponse
    {
        $action = (string) $request->validated('action');
        $permission = in_array($action, ['delete', 'restore'], true)
            ? ContentPermission::DELETE
            : ContentPermission::UPDATE;
        abort_unless($request->user()?->can($permission), 403);

        /** @var User $actor */
        $actor = $request->user();
        $result = $this->tagService->bulk($request->validated(), $actor);

        return ApiResponse::success($result, 'Bulk tag action completed.');
    }
}
