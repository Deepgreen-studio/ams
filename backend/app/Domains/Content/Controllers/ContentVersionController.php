<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Requests\CompareContentVersionsRequest;
use App\Domains\Content\Requests\RestoreContentVersionRequest;
use App\Domains\Content\Resources\ContentResource;
use App\Domains\Content\Resources\ContentVersionResource;
use App\Domains\Content\Services\ContentService;
use App\Domains\Content\Services\ContentVersionService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class ContentVersionController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContentService $contentService,
        private readonly ContentVersionService $contentVersionService
    ) {}

    public function index(string $content): JsonResponse
    {
        $model = $this->contentService->find($content);
        $this->authorize('view', $model);

        $versions = $this->contentVersionService->list($content);

        return ApiResponse::success([
            'content' => [
                'uuid' => $model->uuid,
                'title' => $model->title,
                'version' => (int) $model->version,
            ],
            'versions' => ContentVersionResource::collection($versions),
        ]);
    }

    public function show(string $content, string $version): JsonResponse
    {
        $model = $this->contentService->find($content);
        $this->authorize('view', $model);

        $history = $this->contentVersionService->show($content, $version);

        return ApiResponse::success([
            'version' => (new ContentVersionResource($history))->withSnapshot(),
        ]);
    }

    public function compare(CompareContentVersionsRequest $request, string $content): JsonResponse
    {
        $model = $this->contentService->find($content);
        $this->authorize('view', $model);

        $result = $this->contentVersionService->compare(
            $content,
            (string) $request->validated('from'),
            (string) $request->validated('to')
        );

        return ApiResponse::success([
            'from' => (new ContentVersionResource($result['from']))->withSnapshot(),
            'to' => (new ContentVersionResource($result['to']))->withSnapshot(),
            'comparison' => $result['comparison'],
        ]);
    }

    public function restore(RestoreContentVersionRequest $request, string $content, string $version): JsonResponse
    {
        $model = $this->contentService->find($content);
        $this->authorize('update', $model);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->contentVersionService->restore(
            $content,
            $version,
            $actor,
            $request->validated('reason')
        );

        return ApiResponse::success([
            'content' => new ContentResource($restored->load([
                'type:id,uuid,name,slug,description',
                'status:id,uuid,name,slug,color',
                'category:id,uuid,name,slug',
                'categories:id,uuid,name,slug',
                'tags:id,uuid,name,slug',
                'publisher:id,uuid,full_name,email',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])),
        ], 'Content version restored successfully.');
    }
}
