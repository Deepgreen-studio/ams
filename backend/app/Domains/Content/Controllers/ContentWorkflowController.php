<?php

namespace App\Domains\Content\Controllers;

use App\Domains\Content\Enums\ContentPermission;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Requests\WorkflowCommentRequest;
use App\Domains\Content\Requests\WorkflowRejectRequest;
use App\Domains\Content\Resources\ContentCollection;
use App\Domains\Content\Resources\ContentResource;
use App\Domains\Content\Resources\ContentWorkflowHistoryResource;
use App\Domains\Content\Services\ContentService;
use App\Domains\Content\Services\ContentWorkflowService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentWorkflowController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ContentWorkflowService $workflowService,
        private readonly ContentService $contentService
    ) {}

    public function queue(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        /** @var User $actor */
        $actor = $request->user();
        $items = $this->workflowService->queue($actor, $request->only([
            'search',
            'type',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
        ]));

        return ApiResponse::success([
            'contents' => (new ContentCollection($items))->resolve(),
            'statistics' => $this->contentService->statistics(),
        ]);
    }

    public function history(string $content): JsonResponse
    {
        $model = $this->contentService->find($content);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'history' => ContentWorkflowHistoryResource::collection(
                $this->workflowService->history($content)
            ),
        ]);
    }

    public function submit(WorkflowCommentRequest $request, string $content): JsonResponse
    {
        $model = $this->contentService->find($content);
        $this->authorize('update', $model);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->workflowService->submit($content, $actor, $request->validated('comments'));

        return ApiResponse::success([
            'content' => new ContentResource($updated),
        ], 'Content submitted for review.');
    }

    public function review(WorkflowCommentRequest $request, string $content): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::REVIEW), 403);
        $model = $this->contentService->find($content);
        $this->authorize('view', $model);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->workflowService->review($content, $actor, $request->validated('comments'));

        return ApiResponse::success([
            'content' => new ContentResource($updated),
        ], 'Content marked as reviewed.');
    }

    public function approve(WorkflowCommentRequest $request, string $content): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::APPROVE), 403);
        $model = $this->contentService->find($content);
        $this->authorize('view', $model);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->workflowService->approve($content, $actor, $request->validated('comments'));

        return ApiResponse::success([
            'content' => new ContentResource($updated),
        ], 'Content approved.');
    }

    public function reject(WorkflowRejectRequest $request, string $content): JsonResponse
    {
        $model = $this->contentService->find($content);
        $this->authorize('view', $model);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->workflowService->reject($content, $actor, (string) $request->validated('comments'));

        return ApiResponse::success([
            'content' => new ContentResource($updated),
        ], 'Content rejected.');
    }

    public function publish(WorkflowCommentRequest $request, string $content): JsonResponse
    {
        abort_unless($request->user()?->can(ContentPermission::PUBLISH), 403);
        $model = $this->contentService->find($content);
        $this->authorize('publish', $model);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->workflowService->publish(
            $content,
            $actor,
            $request->validated('comments'),
            $request->validated('published_at')
        );

        return ApiResponse::success([
            'content' => new ContentResource($updated),
        ], 'Content published successfully.');
    }

    public function archive(WorkflowCommentRequest $request, string $content): JsonResponse
    {
        $model = $this->contentService->find($content);
        abort_unless($request->user()?->can(ContentPermission::UPDATE) || $request->user()?->can(ContentPermission::PUBLISH), 403);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->workflowService->archive($content, $actor, $request->validated('comments'));

        return ApiResponse::success([
            'content' => new ContentResource($updated),
        ], 'Content archived.');
    }

    public function returnToDraft(WorkflowCommentRequest $request, string $content): JsonResponse
    {
        $model = $this->contentService->find($content);
        $this->authorize('update', $model);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->workflowService->returnToDraft($content, $actor, $request->validated('comments'));

        return ApiResponse::success([
            'content' => new ContentResource($updated),
        ], 'Content returned to draft.');
    }
}
