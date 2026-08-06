<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Enums\ContentPermission;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Requests\ReplaceMediaLibraryRequest;
use App\Domains\Content\Requests\UpdateMediaLibraryRequest;
use App\Domains\Content\Requests\UploadMediaLibraryRequest;
use App\Domains\Content\Resources\MediaLibraryCollection;
use App\Domains\Content\Resources\MediaLibraryResource;
use App\Domains\Content\Services\MediaLibraryService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaLibraryController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly MediaLibraryService $mediaLibraryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        $media = $this->mediaLibraryService->list($request->only([
            'search',
            'folder',
            'folder_id',
            'root',
            'type',
            'extension',
            'mime_type',
            'trashed',
            'include_versions',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
        ]));

        return ApiResponse::success([
            'media' => (new MediaLibraryCollection($media))->resolve(),
        ]);
    }

    public function store(UploadMediaLibraryRequest $request): JsonResponse
    {
        $this->authorize('create', Content::class);

        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();
        $files = $request->file('files', []);
        if ($request->hasFile('file')) {
            $files = array_merge(is_array($files) ? $files : [], [$request->file('file')]);
        }

        $folder = $validated['folder'] ?? $validated['folder_id'] ?? null;
        $items = $this->mediaLibraryService->upload($files, $folder, $actor, $validated);

        return ApiResponse::success([
            'media' => MediaLibraryResource::collection($items),
        ], 'Media uploaded successfully.', 201);
    }

    public function show(string $media): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        return ApiResponse::success([
            'media' => new MediaLibraryResource($this->mediaLibraryService->show($media)),
        ]);
    }

    public function update(UpdateMediaLibraryRequest $request, string $media): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::UPDATE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->mediaLibraryService->update($media, $request->validated(), $actor);

        return ApiResponse::success([
            'media' => new MediaLibraryResource($updated),
        ], 'Media updated successfully.');
    }

    public function replace(ReplaceMediaLibraryRequest $request, string $media): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::UPDATE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $item = $this->mediaLibraryService->replace(
            $media,
            $request->file('file'),
            $actor,
            $request->validated()
        );

        return ApiResponse::success([
            'media' => new MediaLibraryResource($item),
        ], 'Media file replaced successfully.');
    }

    public function versions(string $media): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        return ApiResponse::success([
            'versions' => MediaLibraryResource::collection($this->mediaLibraryService->versions($media)),
        ]);
    }

    public function restoreVersion(Request $request, string $media, string $version): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::UPDATE), 403);

        /** @var User $actor */
        $actor = $request->user();
        $item = $this->mediaLibraryService->restoreVersion($media, $version, $actor);

        return ApiResponse::success([
            'media' => new MediaLibraryResource($item),
        ], 'Media version restored successfully.');
    }

    public function destroy(string $media): JsonResponse
    {
        abort_unless(request()->user()?->can(ContentPermission::DELETE), 403);

        /** @var User $actor */
        $actor = request()->user();
        $this->mediaLibraryService->delete($media, $actor);

        return ApiResponse::success(null, 'Media deleted successfully.');
    }

    public function restore(string $media): JsonResponse
    {
        abort_unless(request()->user()?->can(ContentPermission::DELETE), 403);

        /** @var User $actor */
        $actor = request()->user();
        $item = $this->mediaLibraryService->restore($media, $actor);

        return ApiResponse::success([
            'media' => new MediaLibraryResource($item),
        ], 'Media restored successfully.');
    }

    public function download(string $media): StreamedResponse
    {
        $this->authorize('viewAny', Content::class);

        return $this->mediaLibraryService->download($media);
    }
}
