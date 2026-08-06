<?php

namespace App\Domains\Settings\Controllers;

use App\Domains\Settings\Models\MediaFile;
use App\Domains\Settings\Requests\UploadMediaRequest;
use App\Domains\Settings\Resources\MediaResource;
use App\Domains\Settings\Services\MediaLibraryService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly MediaLibraryService $mediaLibraryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewMedia', MediaFile::class);

        $media = $this->mediaLibraryService->list($request->only([
            'folder', 'search', 'mime_type', 'extension', 'root', 'sort_by', 'sort_dir', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'media' => [
                'items' => MediaResource::collection($media->items()),
                'meta' => [
                    'current_page' => $media->currentPage(),
                    'last_page' => $media->lastPage(),
                    'per_page' => $media->perPage(),
                    'total' => $media->total(),
                    'from' => $media->firstItem(),
                    'to' => $media->lastItem(),
                ],
            ],
        ]);
    }

    public function store(UploadMediaRequest $request): JsonResponse
    {
        $this->authorize('manageMedia', MediaFile::class);

        /** @var User $actor */
        $actor = $request->user();
        $files = $request->file('files') ?: $request->file('file');
        $uploaded = $this->mediaLibraryService->upload($files, $request->input('folder_id'), $actor);

        return ApiResponse::success([
            'media' => MediaResource::collection($uploaded),
        ], 'Media uploaded successfully.', 201);
    }

    public function destroy(Request $request, string $media): JsonResponse
    {
        $this->authorize('deleteMedia', MediaFile::class);

        /** @var User $actor */
        $actor = $request->user();
        $this->mediaLibraryService->delete($media, $actor);

        return ApiResponse::success(null, 'Media deleted successfully.');
    }
}
