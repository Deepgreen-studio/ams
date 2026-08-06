<?php

namespace App\Domains\Settings\Controllers;

use App\Domains\Settings\Models\FileFolder;
use App\Domains\Settings\Requests\CreateFolderRequest;
use App\Domains\Settings\Requests\UpdateFolderRequest;
use App\Domains\Settings\Resources\FolderResource;
use App\Domains\Settings\Services\FileManagerService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FolderController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly FileManagerService $fileManagerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewFolders', FileFolder::class);

        if ($request->boolean('tree')) {
            return ApiResponse::success([
                'folders' => FolderResource::collection($this->fileManagerService->tree()),
            ]);
        }

        return ApiResponse::success([
            'folders' => FolderResource::collection(
                $this->fileManagerService->list($request->only(['parent_id', 'search']))
            ),
        ]);
    }

    public function store(CreateFolderRequest $request): JsonResponse
    {
        $this->authorize('manageFolders', FileFolder::class);

        /** @var User $actor */
        $actor = $request->user();
        $folder = $this->fileManagerService->create($request->validated(), $actor);

        return ApiResponse::success([
            'folder' => new FolderResource($folder),
        ], 'Folder created successfully.', 201);
    }

    public function update(UpdateFolderRequest $request, string $folder): JsonResponse
    {
        $this->authorize('manageFolders', FileFolder::class);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->fileManagerService->update($folder, $request->validated(), $actor);

        return ApiResponse::success([
            'folder' => new FolderResource($updated),
        ], 'Folder updated successfully.');
    }

    public function destroy(Request $request, string $folder): JsonResponse
    {
        $this->authorize('deleteFolder', FileFolder::class);

        /** @var User $actor */
        $actor = $request->user();
        $this->fileManagerService->delete($folder, $actor);

        return ApiResponse::success(null, 'Folder deleted successfully.');
    }
}
