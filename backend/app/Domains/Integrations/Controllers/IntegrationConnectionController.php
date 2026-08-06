<?php

namespace App\Domains\Integrations\Controllers;

use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Requests\ExecuteIntegrationRequestRequest;
use App\Domains\Integrations\Requests\UpdateIntegrationConfigurationRequest;
use App\Domains\Integrations\Resources\IntegrationConnectionLogCollection;
use App\Domains\Integrations\Resources\IntegrationConnectionLogResource;
use App\Domains\Integrations\Resources\IntegrationResource;
use App\Domains\Integrations\Services\IntegrationConnectionService;
use App\Domains\Integrations\Services\IntegrationService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationConnectionController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly IntegrationConnectionService $connectionService,
        private readonly IntegrationService $integrationService,
    ) {}

    public function testConnection(Request $request, string $integration): JsonResponse
    {
        $model = $this->integrationService->find($integration);
        $this->authorize('manage', $model);

        /** @var User $actor */
        $actor = $request->user();
        $result = $this->connectionService->testConnection($integration, $actor);

        return ApiResponse::success([
            'integration' => new IntegrationResource($result['integration']),
            'response' => $result['response'],
            'log' => new IntegrationConnectionLogResource($result['log']),
        ], $result['response']['successful'] ? 'Connection test succeeded.' : 'Connection test failed.');
    }

    public function testAuthentication(Request $request, string $integration): JsonResponse
    {
        $model = $this->integrationService->find($integration);
        $this->authorize('manage', $model);

        /** @var User $actor */
        $actor = $request->user();
        $result = $this->connectionService->testAuthentication($integration, $actor);

        return ApiResponse::success([
            'integration' => new IntegrationResource($result['integration']),
            'response' => $result['response'],
            'log' => new IntegrationConnectionLogResource($result['log']),
        ], $result['response']['successful'] ? 'Authentication test succeeded.' : 'Authentication test failed.');
    }

    public function execute(ExecuteIntegrationRequestRequest $request, string $integration): JsonResponse
    {
        $model = $this->integrationService->find($integration);
        $this->authorize('manage', $model);

        /** @var User $actor */
        $actor = $request->user();
        $files = [];
        if ($request->hasFile('file')) {
            $files['file'] = $request->file('file');
        }

        $result = $this->connectionService->executeRequest(
            $integration,
            $request->validated(),
            $actor,
            $files
        );

        return ApiResponse::success([
            'integration' => new IntegrationResource($result['integration']),
            'response' => $result['response'],
            'log' => new IntegrationConnectionLogResource($result['log']),
        ], $result['response']['successful'] ? 'Request executed successfully.' : 'Request completed with errors.');
    }

    public function updateConfiguration(UpdateIntegrationConfigurationRequest $request, string $integration): JsonResponse
    {
        $model = $this->integrationService->find($integration);
        $this->authorize('update', $model);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->connectionService->updateConfiguration($integration, $request->validated(), $actor);

        return ApiResponse::success([
            'integration' => new IntegrationResource($updated->load([
                'company:id,uuid,company_name,status',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])),
        ], 'API configuration updated successfully.');
    }

    public function history(Request $request, string $integration): JsonResponse
    {
        $model = $this->integrationService->find($integration);
        $this->authorize('view', $model);

        $logs = $this->connectionService->listHistory($integration, $request->only([
            'search', 'request_type', 'method', 'success', 'sort_by', 'sort_dir', 'per_page', 'page',
        ]));

        return ApiResponse::success([
            'history' => (new IntegrationConnectionLogCollection($logs))->resolve(),
        ]);
    }

    public function showHistory(string $integration, string $log): JsonResponse
    {
        $model = $this->integrationService->find($integration);
        $this->authorize('view', $model);

        $entry = $this->connectionService->showHistoryEntry($integration, $log);

        return ApiResponse::success([
            'log' => new IntegrationConnectionLogResource($entry),
        ]);
    }
}
