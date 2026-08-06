<?php

namespace App\Domains\Scheduler\Controllers;

use App\Domains\Scheduler\Models\ScheduledJob;
use App\Domains\Scheduler\Requests\IndexScheduledJobRequest;
use App\Domains\Scheduler\Requests\StoreScheduledJobRequest;
use App\Domains\Scheduler\Requests\UpdateScheduledJobRequest;
use App\Domains\Scheduler\Resources\ScheduledJobLogResource;
use App\Domains\Scheduler\Resources\ScheduledJobResource;
use App\Domains\Scheduler\Resources\ScheduledJobRunResource;
use App\Domains\Scheduler\Services\ScheduledJobService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduledJobController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ScheduledJobService $jobService,
    ) {}

    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', ScheduledJob::class);
        $data = $this->jobService->dashboard();

        return ApiResponse::success([
            'statistics' => $data['statistics'],
            'run_statistics' => $data['run_statistics'],
            'catalog' => $data['catalog'],
            'recent_runs' => ScheduledJobRunResource::collection($data['recent_runs'])->resolve(),
            'recent_failed' => ScheduledJobRunResource::collection($data['recent_failed'])->resolve(),
        ]);
    }

    public function catalog(): JsonResponse
    {
        $this->authorize('viewAny', ScheduledJob::class);

        return ApiResponse::success(['catalog' => $this->jobService->catalog()]);
    }

    public function index(IndexScheduledJobRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ScheduledJob::class);
        $paginator = $this->jobService->paginate($request->filters());

        return ApiResponse::success([
            'jobs' => [
                'items' => ScheduledJobResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
            'catalog' => $this->jobService->catalog(),
        ]);
    }

    public function show(string $job): JsonResponse
    {
        $model = $this->jobService->find($job);
        $this->authorize('view', $model);

        return ApiResponse::success([
            'job' => new ScheduledJobResource($model),
            'catalog' => $this->jobService->catalog(),
        ]);
    }

    public function store(StoreScheduledJobRequest $request): JsonResponse
    {
        $this->authorize('create', ScheduledJob::class);

        /** @var User $actor */
        $actor = $request->user();
        $job = $this->jobService->create($request->validated(), $actor);

        return ApiResponse::success([
            'job' => new ScheduledJobResource($job),
        ], 'Scheduled job created.', 201);
    }

    public function update(UpdateScheduledJobRequest $request, string $job): JsonResponse
    {
        $existing = $this->jobService->find($job);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->jobService->update($job, $request->validated(), $actor);

        return ApiResponse::success([
            'job' => new ScheduledJobResource($updated),
        ], 'Scheduled job updated.');
    }

    public function destroy(string $job): JsonResponse
    {
        $existing = $this->jobService->find($job);
        $this->authorize('delete', $existing);
        $this->jobService->delete($job, request()->user());

        return ApiResponse::success(null, 'Scheduled job deleted.');
    }

    public function toggle(Request $request, string $job): JsonResponse
    {
        $existing = $this->jobService->find($job);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $enabled = $request->has('is_enabled') ? (bool) $request->boolean('is_enabled') : null;
        $updated = $this->jobService->toggle($job, $actor, $enabled);

        return ApiResponse::success([
            'job' => new ScheduledJobResource($updated),
        ], $updated->is_enabled ? 'Scheduled job enabled.' : 'Scheduled job disabled.');
    }

    public function runNow(string $job): JsonResponse
    {
        $existing = $this->jobService->find($job);
        $this->authorize('manage', ScheduledJob::class);
        $result = $this->jobService->runNow($job, request()->user());

        return ApiResponse::success(['result' => $result], 'Scheduled job dispatched.');
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ScheduledJob::class);
        $paginator = $this->jobService->paginateRuns($request->query());

        return ApiResponse::success([
            'statistics' => $this->jobService->dashboard()['run_statistics'],
            'runs' => [
                'items' => ScheduledJobRunResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function running(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ScheduledJob::class);
        $paginator = $this->jobService->paginateRuns(array_merge($request->query(), ['status' => 'running']));

        return ApiResponse::success([
            'runs' => [
                'items' => ScheduledJobRunResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function failed(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ScheduledJob::class);
        $paginator = $this->jobService->paginateRuns(array_merge($request->query(), ['status' => 'failed']));

        return ApiResponse::success([
            'runs' => [
                'items' => ScheduledJobRunResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function showRun(string $run): JsonResponse
    {
        $this->authorize('viewAny', ScheduledJob::class);

        return ApiResponse::success([
            'run' => new ScheduledJobRunResource($this->jobService->findRun($run)),
        ]);
    }

    public function retry(string $run): JsonResponse
    {
        $this->authorize('retry', ScheduledJob::class);
        $result = $this->jobService->retryRun($run, request()->user());

        return ApiResponse::success(['result' => $result], 'Scheduled job run retried.');
    }

    public function logs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ScheduledJob::class);
        $paginator = $this->jobService->paginateLogs($request->query());

        return ApiResponse::success([
            'logs' => [
                'items' => ScheduledJobLogResource::collection($paginator->items())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', ScheduledJob::class);
        $data = $this->jobService->dashboard();

        return ApiResponse::success([
            'jobs' => $data['statistics'],
            'runs' => $data['run_statistics'],
        ]);
    }
}
