<?php

namespace App\Domains\Analytics\Controllers;

use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Domains\Analytics\Models\AnalyticsSubject;
use App\Domains\Analytics\Requests\CreateDashboardFromTemplateRequest;
use App\Domains\Analytics\Requests\FilterEnterpriseAnalyticsRequest;
use App\Domains\Analytics\Requests\IndexAnalyticsDashboardRequest;
use App\Domains\Analytics\Requests\SaveAnalyticsDashboardLayoutRequest;
use App\Domains\Analytics\Requests\ShareAnalyticsDashboardRequest;
use App\Domains\Analytics\Requests\StoreAnalyticsDashboardRequest;
use App\Domains\Analytics\Requests\UpdateAnalyticsDashboardRequest;
use App\Domains\Analytics\Resources\AnalyticsDashboardCollection;
use App\Domains\Analytics\Resources\AnalyticsDashboardResource;
use App\Domains\Analytics\Services\AnalyticsDashboardService;
use App\Domains\Analytics\Services\AnalyticsDashboardShareService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class AnalyticsDashboardController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AnalyticsDashboardService $dashboardService,
        private readonly AnalyticsDashboardShareService $shareService,
    ) {}

    public function index(IndexAnalyticsDashboardRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $dashboards = $this->dashboardService->paginate($request->filters(), $request->user());

        return ApiResponse::success([
            'dashboards' => (new AnalyticsDashboardCollection($dashboards))->resolve(),
        ]);
    }

    public function templates(): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $templates = $this->dashboardService->templates();

        return ApiResponse::success([
            'templates' => AnalyticsDashboardResource::collection($templates)->resolve(),
        ]);
    }

    public function store(StoreAnalyticsDashboardRequest $request): JsonResponse
    {
        $this->authorize('create', AnalyticsDashboard::class);

        $dashboard = $this->dashboardService->create($request->validated(), $request->user());

        return ApiResponse::success([
            'dashboard' => new AnalyticsDashboardResource($dashboard),
        ], 'Analytics dashboard created.', 201);
    }

    public function fromTemplate(CreateDashboardFromTemplateRequest $request, string $dashboard): JsonResponse
    {
        $this->authorize('create', AnalyticsDashboard::class);

        $template = $this->dashboardService->find($dashboard);
        $created = $this->dashboardService->createFromTemplate($template, $request->validated(), $request->user());

        return ApiResponse::success([
            'dashboard' => new AnalyticsDashboardResource($created),
        ], 'Dashboard created from template.', 201);
    }

    public function show(string $dashboard): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $model = $this->dashboardService->find($dashboard);

        return ApiResponse::success([
            'dashboard' => new AnalyticsDashboardResource($model),
        ]);
    }

    public function update(UpdateAnalyticsDashboardRequest $request, string $dashboard): JsonResponse
    {
        $model = $this->dashboardService->find($dashboard);
        $this->authorize('update', $model);

        $updated = $this->dashboardService->update($model, $request->validated(), $request->user());

        return ApiResponse::success([
            'dashboard' => new AnalyticsDashboardResource($updated),
        ], 'Analytics dashboard updated.');
    }

    public function saveLayout(SaveAnalyticsDashboardLayoutRequest $request, string $dashboard): JsonResponse
    {
        $model = $this->dashboardService->find($dashboard);
        $this->authorize('update', $model);

        $updated = $this->dashboardService->saveLayout(
            $model,
            $request->validated('widgets'),
            $request->validated('layout'),
            $request->user()
        );

        return ApiResponse::success([
            'dashboard' => new AnalyticsDashboardResource($updated),
        ], 'Dashboard layout saved.');
    }

    public function destroy(string $dashboard): JsonResponse
    {
        $model = $this->dashboardService->find($dashboard);
        $this->authorize('delete', $model);

        $this->dashboardService->delete($model, request()->user());

        return ApiResponse::success(null, 'Analytics dashboard deleted.');
    }

    public function duplicate(string $dashboard): JsonResponse
    {
        $model = $this->dashboardService->find($dashboard);
        $this->authorize('create', AnalyticsDashboard::class);

        $copy = $this->dashboardService->duplicate($model, request()->user());

        return ApiResponse::success([
            'dashboard' => new AnalyticsDashboardResource($copy),
        ], 'Analytics dashboard duplicated.', 201);
    }

    public function data(FilterEnterpriseAnalyticsRequest $request, string $dashboard): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $model = $this->dashboardService->find($dashboard);
        $resolved = $this->dashboardService->resolveData($model, $request->validated());

        return ApiResponse::success([
            'dashboard' => new AnalyticsDashboardResource($resolved['dashboard']),
            'filters' => $resolved['filters'],
            'widgets' => $resolved['widgets'],
        ]);
    }

    public function shares(string $dashboard): JsonResponse
    {
        $this->authorize('viewAny', AnalyticsSubject::class);

        $model = $this->dashboardService->find($dashboard);
        $shares = $this->shareService->list($model);

        return ApiResponse::success([
            'shares' => $this->shareService->enrichShares($shares),
        ]);
    }

    public function share(ShareAnalyticsDashboardRequest $request, string $dashboard): JsonResponse
    {
        $model = $this->dashboardService->find($dashboard);
        $this->authorize('update', $model);

        $share = $this->shareService->share($model, $request->validated(), $request->user());

        return ApiResponse::success([
            'share' => $this->shareService->enrichShares(collect([$share]))[0],
            'dashboard' => new AnalyticsDashboardResource($model->fresh(['owner', 'creator', 'updater'])),
        ], 'Dashboard shared.', 201);
    }

    public function revokeShare(string $dashboard, string $share): JsonResponse
    {
        $model = $this->dashboardService->find($dashboard);
        $this->authorize('update', $model);

        $shareModel = app(\App\Domains\Analytics\Repositories\AnalyticsDashboardShareRepository::class)
            ->findByUuidOrFail($share);

        $this->shareService->revoke($model, $shareModel, request()->user());

        return ApiResponse::success(null, 'Dashboard share revoked.');
    }
}
