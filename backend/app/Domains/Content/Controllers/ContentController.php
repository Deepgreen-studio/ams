<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Models\Content;
use App\Domains\Content\Requests\AutosaveContentRequest;
use App\Domains\Content\Requests\PublishContentRequest;
use App\Domains\Content\Requests\StoreContentRequest;
use App\Domains\Content\Requests\UpdateContentRequest;
use App\Domains\Content\Requests\UploadContentMediaRequest;
use App\Domains\Content\Resources\ContentCollection;
use App\Domains\Content\Resources\ContentResource;
use App\Domains\Content\Services\ContentService;
use App\Domains\Content\Services\MediaLibraryService;
use App\Domains\Content\Services\ContentWorkflowService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContentService $contentService,
        private readonly MediaLibraryService $mediaLibraryService,
        private readonly ContentWorkflowService $contentWorkflowService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        return ApiResponse::success([
            'statistics' => $this->contentService->statistics(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        $contents = $this->contentService->list($request->only([
            'search',
            'type',
            'status',
            'category',
            'tag',
            'content_type_id',
            'content_status_id',
            'content_category_id',
            'is_featured',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
            'trashed',
        ]));

        return ApiResponse::success([
            'contents' => (new ContentCollection($contents))->resolve(),
            'statistics' => $this->contentService->statistics(),
        ]);
    }

    public function store(StoreContentRequest $request): JsonResponse
    {
        $this->authorize('create', Content::class);

        /** @var User $actor */
        $actor = $request->user();
        $content = $this->contentService->create($request->validated(), $actor);

        return ApiResponse::success([
            'content' => new ContentResource($content),
        ], 'Content created successfully.', 201);
    }

    public function show(string $content): JsonResponse
    {
        $model = $this->contentService->show($content);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'content' => new ContentResource($model),
        ]);
    }

    public function update(UpdateContentRequest $request, string $content): JsonResponse
    {
        $existing = $this->contentService->find($content);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->contentService->update($content, $request->validated(), $actor);

        return ApiResponse::success([
            'content' => new ContentResource($updated),
        ], 'Content updated successfully.');
    }

    public function destroy(Request $request, string $content): JsonResponse
    {
        $existing = $this->contentService->find($content);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->contentService->delete($content, $actor);

        return ApiResponse::success(null, 'Content deleted successfully.');
    }

    public function restore(Request $request, string $content): JsonResponse
    {
        $existing = $this->contentService->find($content, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->contentService->restore($content, $actor);

        return ApiResponse::success([
            'content' => new ContentResource($restored),
        ], 'Content restored successfully.');
    }

    public function publish(PublishContentRequest $request, string $content): JsonResponse
    {
        $existing = $this->contentService->find($content);
        $this->authorize('publish', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $published = $this->contentWorkflowService->publish(
            $content,
            $actor,
            null,
            $request->validated('published_at')
        );

        return ApiResponse::success([
            'content' => new ContentResource($published),
        ], 'Content published successfully.');
    }

    public function unpublish(Request $request, string $content): JsonResponse
    {
        $existing = $this->contentService->find($content);
        $this->authorize('publish', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $unpublished = $this->contentService->unpublish($content, $actor);

        return ApiResponse::success([
            'content' => new ContentResource($unpublished),
        ], 'Content unpublished successfully.');
    }

    public function autosave(AutosaveContentRequest $request, string $content): JsonResponse
    {
        $existing = $this->contentService->find($content);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $saved = $this->contentService->autosave($content, $request->validated(), $actor);

        return ApiResponse::success([
            'content' => new ContentResource($saved),
            'last_autosaved_at' => $saved->last_autosaved_at,
        ], 'Draft autosaved.');
    }

    public function uploadMedia(UploadContentMediaRequest $request): JsonResponse
    {
        $this->authorize('create', Content::class);

        /** @var User $actor */
        $actor = $request->user();
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');
        $media = $this->mediaLibraryService->uploadForEditor($file, $actor);

        return ApiResponse::success([
            'media' => $media,
        ], 'Media uploaded successfully.', 201);
    }
}
