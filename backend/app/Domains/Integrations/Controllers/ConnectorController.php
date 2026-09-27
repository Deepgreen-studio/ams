<?php

namespace App\Domains\Integrations\Controllers;

use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Domains\Integrations\Requests\UpdateConnectorCredentialsRequest;
use App\Domains\Integrations\Services\ConnectorFrameworkService;
use App\Domains\Integrations\Services\CredentialVault;
use App\Domains\Integrations\Services\OAuthTokenService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class ConnectorController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ConnectorFrameworkService $connectorFrameworkService,
        private readonly IntegrationRepository $integrationRepository,
        private readonly CredentialVault $credentialVault,
        private readonly OAuthTokenService $oauthTokenService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Integration::class);

        return ApiResponse::success([
            'connectors' => $this->connectorFrameworkService->catalog(),
        ], 'Connector catalog loaded.');
    }

    public function show(string $integration): JsonResponse
    {
        $record = $this->integrationRepository->findByIdentifierOrFail($integration);
        $this->authorize('view', $record);

        return ApiResponse::success([
            'connector' => $this->connectorFrameworkService->describe($record),
        ], 'Connector profile loaded.');
    }

    public function refreshOauth(string $integration): JsonResponse
    {
        $record = $this->integrationRepository->findByIdentifierOrFail($integration);
        $this->authorize('manage', $record);

        /** @var User $actor */
        $actor = auth()->user();
        $updated = $this->oauthTokenService->refresh($record, $actor);

        return ApiResponse::success([
            'connector' => $this->connectorFrameworkService->describe($updated),
        ], 'OAuth token refreshed.');
    }

    public function updateCredentials(UpdateConnectorCredentialsRequest $request, string $integration): JsonResponse
    {
        $record = $this->integrationRepository->findByIdentifierOrFail($integration);
        $this->authorize('update', $record);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->credentialVault->merge(
            $record,
            (array) $request->validated('credentials'),
            $actor,
        );

        return ApiResponse::success([
            'connector' => $this->connectorFrameworkService->describe($updated),
        ], 'Credentials stored.');
    }
}
