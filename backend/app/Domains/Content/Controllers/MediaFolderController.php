<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Enums\ContentPermission;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Requests\StoreMediaFolderRequest;
use App\Domains\Content\Requests\UpdateMediaFolderRequest;
use App\Domains\Content\Resources\MediaFolderResource;
use App\Domains\Content\Services\MediaFolderService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class MediaFolderController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly MediaFolderService $mediaFolderService
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        return ApiResponse::success([
            'folders' => MediaFolderResource::collection($this->mediaFolderService->list()),
        ]);
    }

    public function tree(): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        return ApiResponse::success([
            'folders' => MediaFolderResource::collection($this->mediaFolderService->tree()),
        ]);
    }

    public function store(StoreMediaFolderRequest $request): JsonResponse
    {
        $this->authorize('create', Content::class);

        /** @var User $actor */
        $actor = $request->user();
        $folder = $this->mediaFolderService->create($request->validated(), $actor);

        return ApiResponse::success([
            'folder' => new MediaFolderResource($folder),
        ], 'Folder created successfully.', 201);
    }

    public function show(string $folder): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        return ApiResponse::success([
            'folder' => new MediaFolderResource($this->mediaFolderService->show($folder)),
        ]);
    }

    public function update(UpdateMediaFolderRequest $request, string $folder): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::UPDATE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->mediaFolderService->update($folder, $request->validated(), $actor);

        return ApiResponse::success([
            'folder' => new MediaFolderResource($updated),
        ], 'Folder updated successfully.');
    }

    public function destroy(string $folder): JsonResponse
    {
        abort_unless(request()->user()?->can(ContentPermission::DELETE), 403);

        /** @var User $actor */
        $actor = request()->user();
        $this->mediaFolderService->delete($folder, $actor);

        return ApiResponse::success(null, 'Folder deleted successfully.');
    }

    public function restore(string $folder): JsonResponse
    {
        abort_unless(request()->user()?->can(ContentPermission::DELETE), 403);

        /** @var User $actor */
        $actor = request()->user();
        $restored = $this->mediaFolderService->restore($folder, $actor);

        return ApiResponse::success([
            'folder' => new MediaFolderResource($restored),
        ], 'Folder restored successfully.');
    }
}
