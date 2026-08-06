<?php

namespace App\Domains\Applications\Controllers;

use App\Domains\Applications\Requests\ApproveApplicationReleaseRequest;
use App\Domains\Applications\Requests\DeployApplicationReleaseRequest;
use App\Domains\Applications\Requests\RejectApplicationReleaseRequest;
use App\Domains\Applications\Requests\RollbackApplicationReleaseRequest;
use App\Domains\Applications\Requests\ScheduleApplicationReleaseRequest;
use App\Domains\Applications\Requests\StoreApplicationReleaseRequest;
use App\Domains\Applications\Requests\UpdateApplicationReleaseRequest;
use App\Domains\Applications\Resources\ApplicationReleaseCollection;
use App\Domains\Applications\Resources\ApplicationReleaseResource;
use App\Domains\Applications\Services\ApplicationReleaseService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationReleaseController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApplicationReleaseService $releaseService
    ) {}

    public function dashboard(string $application): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('view', $app);

        $data = $this->releaseService->dashboard($application);

        return ApiResponse::success([
            'application' => [
                'uuid' => $app->uuid,
                'name' => $app->name,
            ],
            'summary' => $data['summary'],
            'releases' => ApplicationReleaseResource::collection($data['releases']),
        ]);
    }

    public function calendar(Request $request, string $application): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('view', $app);

        $releases = $this->releaseService->calendar(
            $application,
            $request->query('from'),
            $request->query('to')
        );

        return ApiResponse::success([
            'application' => [
                'uuid' => $app->uuid,
                'name' => $app->name,
            ],
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'releases' => ApplicationReleaseResource::collection($releases),
        ]);
    }

    public function timeline(Request $request, string $application): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('view', $app);

        $releases = $this->releaseService->timeline(
            $application,
            (int) $request->query('limit', 40)
        );

        return ApiResponse::success([
            'application' => [
                'uuid' => $app->uuid,
                'name' => $app->name,
            ],
            'releases' => ApplicationReleaseResource::collection($releases),
        ]);
    }

    public function index(Request $request, string $application): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('view', $app);

        $releases = $this->releaseService->list($application, $request->only([
            'search',
            'status',
            'release_type',
            'approval_status',
            'rollback_status',
            'environment',
            'environment_id',
            'sort_by',
            'sort_dir',
            'per_page',
            'page',
        ]));

        return ApiResponse::success([
            'releases' => (new ApplicationReleaseCollection($releases))->resolve(),
        ]);
    }

    public function store(StoreApplicationReleaseRequest $request, string $application): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $release = $this->releaseService->create($application, $request->validated(), $actor);

        return ApiResponse::success([
            'release' => new ApplicationReleaseResource($release),
        ], 'Release created successfully.', 201);
    }

    public function show(string $application, string $release): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('view', $app);

        $model = $this->releaseService->find($application, $release);

        return ApiResponse::success([
            'release' => new ApplicationReleaseResource($model),
        ]);
    }

    public function update(UpdateApplicationReleaseRequest $request, string $application, string $release): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->releaseService->update($application, $release, $request->validated(), $actor);

        return ApiResponse::success([
            'release' => new ApplicationReleaseResource($updated),
        ], 'Release updated successfully.');
    }

    public function destroy(Request $request, string $application, string $release): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $this->releaseService->delete($application, $release, $actor);

        return ApiResponse::success([], 'Release deleted successfully.');
    }

    public function schedule(ScheduleApplicationReleaseRequest $request, string $application, string $release): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->releaseService->schedule($application, $release, $request->validated(), $actor);

        return ApiResponse::success([
            'release' => new ApplicationReleaseResource($updated),
        ], 'Release scheduled successfully.');
    }

    public function submitApproval(Request $request, string $application, string $release): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->releaseService->submitApproval($application, $release, $actor);

        return ApiResponse::success([
            'release' => new ApplicationReleaseResource($updated),
        ], 'Release submitted for approval.');
    }

    public function approve(ApproveApplicationReleaseRequest $request, string $application, string $release): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->releaseService->approve($application, $release, $request->validated(), $actor);

        return ApiResponse::success([
            'release' => new ApplicationReleaseResource($updated),
        ], 'Release approved successfully.');
    }

    public function reject(RejectApplicationReleaseRequest $request, string $application, string $release): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->releaseService->reject($application, $release, $request->validated(), $actor);

        return ApiResponse::success([
            'release' => new ApplicationReleaseResource($updated),
        ], 'Release rejected.');
    }

    public function deploy(DeployApplicationReleaseRequest $request, string $application, string $release): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->releaseService->deploy($application, $release, $request->validated(), $actor);

        return ApiResponse::success([
            'release' => new ApplicationReleaseResource($updated),
        ], 'Release deployment recorded.');
    }

    public function rollback(RollbackApplicationReleaseRequest $request, string $application, string $release): JsonResponse
    {
        $app = $this->releaseService->resolveApplication($application);
        $this->authorize('update', $app);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->releaseService->rollback($application, $release, $request->validated(), $actor);

        return ApiResponse::success([
            'release' => new ApplicationReleaseResource($updated),
        ], 'Release rolled back successfully.');
    }
}
