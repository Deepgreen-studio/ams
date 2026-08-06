<?php

namespace App\Domains\Queue\Controllers;

use App\Domains\Queue\Models\QueueJobTrack;
use App\Domains\Queue\Requests\DispatchSampleQueueJobRequest;
use App\Domains\Queue\Resources\QueueJobTrackCollection;
use App\Domains\Queue\Resources\QueueJobTrackResource;
use App\Domains\Queue\Services\QueueMonitorService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly QueueMonitorService $queueMonitorService
    ) {}

    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', QueueJobTrack::class);
        $data = $this->queueMonitorService->dashboard();

        return ApiResponse::success([
            'connection' => $data['connection'],
            'worker_queues' => $data['worker_queues'],
            'queue_sizes' => $data['queue_sizes'],
            'pending' => $data['pending'],
            'failed_count' => $data['failed_count'],
            'tracks' => $data['tracks'],
            'by_type' => $data['by_type'],
            'recent_failed' => $data['recent_failed'],
            'recent_tracks' => QueueJobTrackResource::collection(collect($data['recent_tracks']))->resolve(),
            'status' => $data['status'],
        ]);
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', QueueJobTrack::class);

        return ApiResponse::success($this->queueMonitorService->statistics());
    }

    public function tracks(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QueueJobTrack::class);
        $tracks = $this->queueMonitorService->listTracks($request->only([
            'status', 'type', 'queue', 'priority', 'search', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'tracks' => (new QueueJobTrackCollection($tracks))->resolve(),
        ]);
    }

    public function running(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QueueJobTrack::class);
        $tracks = $this->queueMonitorService->listRunning($request->only([
            'type', 'queue', 'priority', 'search', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'tracks' => (new QueueJobTrackCollection($tracks))->resolve(),
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QueueJobTrack::class);
        $jobs = $this->queueMonitorService->listPendingJobs($request->only([
            'queue', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'jobs' => [
                'items' => collect($jobs->items())->map(fn ($job) => [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'attempts' => $job->attempts,
                    'reserved_at' => $job->reserved_at,
                    'available_at' => $job->available_at,
                    'created_at' => $job->created_at,
                ])->values(),
                'meta' => [
                    'current_page' => $jobs->currentPage(),
                    'from' => $jobs->firstItem(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'to' => $jobs->lastItem(),
                    'total' => $jobs->total(),
                ],
            ],
        ]);
    }

    public function failed(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QueueJobTrack::class);
        $jobs = $this->queueMonitorService->listFailed($request->only([
            'queue', 'search', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'failed' => [
                'items' => collect($jobs->items())->map(fn ($job) => [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'exception' => \Illuminate\Support\Str::limit((string) $job->exception, 240),
                    'failed_at' => $job->failed_at,
                    'display_name' => data_get(json_decode((string) $job->payload, true), 'displayName')
                        ?: data_get(json_decode((string) $job->payload, true), 'data.commandName')
                        ?: 'UnknownJob',
                ])->values(),
                'meta' => [
                    'current_page' => $jobs->currentPage(),
                    'from' => $jobs->firstItem(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'to' => $jobs->lastItem(),
                    'total' => $jobs->total(),
                ],
            ],
        ]);
    }

    public function showFailed(string $failed): JsonResponse
    {
        $this->authorize('viewAny', QueueJobTrack::class);

        return ApiResponse::success([
            'failed' => $this->queueMonitorService->showFailed($failed),
        ]);
    }

    public function retryFailed(Request $request, string $failed): JsonResponse
    {
        $this->authorize('retry', QueueJobTrack::class);
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->queueMonitorService->retryFailed($failed, $actor);

        return ApiResponse::success($result, 'Failed job queued for retry.');
    }

    public function retryAllFailed(Request $request): JsonResponse
    {
        $this->authorize('retry', QueueJobTrack::class);
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->queueMonitorService->retryAllFailed($actor);

        return ApiResponse::success($result, 'All failed jobs queued for retry.');
    }

    public function forgetFailed(Request $request, string $failed): JsonResponse
    {
        $this->authorize('manage', QueueJobTrack::class);
        $this->queueMonitorService->forgetFailed($failed);

        return ApiResponse::success(null, 'Failed job removed.');
    }

    public function flushFailed(Request $request): JsonResponse
    {
        $this->authorize('manage', QueueJobTrack::class);
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->queueMonitorService->flushFailed($actor);

        return ApiResponse::success($result, 'Failed jobs flushed.');
    }

    public function restart(Request $request): JsonResponse
    {
        $this->authorize('manage', QueueJobTrack::class);
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->queueMonitorService->restartWorkers($actor);

        return ApiResponse::success($result, 'Queue restart signal sent.');
    }

    public function dispatchSample(DispatchSampleQueueJobRequest $request): JsonResponse
    {
        $this->authorize('manage', QueueJobTrack::class);
        /** @var User $actor */
        $actor = $request->user();
        $result = $this->queueMonitorService->dispatchNotificationSample($request->validated(), $actor);

        return ApiResponse::success([
            'track' => new QueueJobTrackResource($result['track']),
            'queue' => $result['queue'],
            'delay_seconds' => $result['delay_seconds'],
        ], 'Sample notification job dispatched.', 201);
    }
}
