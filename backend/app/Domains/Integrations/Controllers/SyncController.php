<?php

namespace App\Domains\Integrations\Controllers;

use App\Domains\Integrations\Models\SyncConfig;
use App\Domains\Integrations\Requests\RunSyncRequest;
use App\Domains\Integrations\Requests\StoreSyncConfigRequest;
use App\Domains\Integrations\Requests\UpdateSyncConfigRequest;
use App\Domains\Integrations\Resources\SyncConfigCollection;
use App\Domains\Integrations\Resources\SyncConfigResource;
use App\Domains\Integrations\Resources\SyncLogCollection;
use App\Domains\Integrations\Resources\SyncRunCollection;
use App\Domains\Integrations\Resources\SyncRunResource;
use App\Domains\Integrations\Services\IntegrationSyncService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly IntegrationSyncService $syncService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SyncConfig::class);
        $data = $this->syncService->dashboard($request->query('company'));

        return ApiResponse::success([
            'totals' => $data['totals'],
            'recent_runs' => SyncRunResource::collection($data['recent_runs'])->resolve(),
            'configs' => (new SyncConfigCollection($data['configs']))->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SyncConfig::class);
        $configs = $this->syncService->listConfigs($request->only([
            'search', 'direction', 'trigger_type', 'is_enabled', 'company', 'company_id',
            'integration_id', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'configs' => (new SyncConfigCollection($configs))->resolve(),
        ]);
    }

    public function store(StoreSyncConfigRequest $request): JsonResponse
    {
        $this->authorize('create', SyncConfig::class);
        /** @var User $actor */
        $actor = $request->user();
        $config = $this->syncService->createConfig($request->validated(), $actor);

        return ApiResponse::success([
            'config' => new SyncConfigResource($config),
        ], 'Sync configuration created successfully.', 201);
    }

    public function show(string $sync): JsonResponse
    {
        $config = $this->syncService->showConfig($sync);
        $this->authorize('view', $config);

        return ApiResponse::success([
            'config' => new SyncConfigResource($config),
        ]);
    }

    public function update(UpdateSyncConfigRequest $request, string $sync): JsonResponse
    {
        $config = $this->syncService->findConfig($sync);
        $this->authorize('update', $config);
        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->syncService->updateConfig($sync, $request->validated(), $actor);

        return ApiResponse::success([
            'config' => new SyncConfigResource($updated),
        ], 'Sync configuration updated successfully.');
    }

    public function destroy(Request $request, string $sync): JsonResponse
    {
        $config = $this->syncService->findConfig($sync);
        $this->authorize('delete', $config);
        /** @var User $actor */
        $actor = $request->user();
        $this->syncService->deleteConfig($sync, $actor);

        return ApiResponse::success(null, 'Sync configuration deleted successfully.');
    }

    public function run(RunSyncRequest $request, string $sync): JsonResponse
    {
        $config = $this->syncService->findConfig($sync);
        $this->authorize('run', $config);
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();
        $result = $this->syncService->run(
            $sync,
            $actor,
            trigger: 'manual',
            mode: $validated['mode'] ?? null,
            background: (bool) ($validated['background'] ?? false),
        );

        return ApiResponse::success([
            'config' => new SyncConfigResource($result['config']),
            'run' => new SyncRunResource($result['run']),
        ], 'Sync started successfully.');
    }

    public function runs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SyncConfig::class);
        $runs = $this->syncService->listRuns($request->only([
            'status', 'trigger', 'mode', 'sync_config', 'sync_config_id',
            'company', 'company_id', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'runs' => (new SyncRunCollection($runs))->resolve(),
        ]);
    }

    public function showRun(string $run): JsonResponse
    {
        $this->authorize('viewAny', SyncConfig::class);
        $result = $this->syncService->showRun($run);

        return ApiResponse::success([
            'run' => new SyncRunResource($result['run']),
        ]);
    }

    public function logs(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SyncConfig::class);
        $logs = $this->syncService->listLogs($request->only([
            'level', 'action', 'search', 'sync_run', 'sync_run_id',
            'sync_config', 'sync_config_id', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'logs' => (new SyncLogCollection($logs))->resolve(),
        ]);
    }
}
