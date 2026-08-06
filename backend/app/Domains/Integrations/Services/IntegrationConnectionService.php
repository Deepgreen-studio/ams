<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Integrations\Enums\ConnectionRequestType;
use App\Domains\Integrations\Enums\IntegrationHealthStatus;
use App\Domains\Integrations\Events\IntegrationConfigurationUpdated;
use App\Domains\Integrations\Events\IntegrationConnectionExecuted;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Models\IntegrationConnectionLog;
use App\Domains\Integrations\Repositories\IntegrationConnectionLogRepository;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Services\Http\ApiClientService;
use App\Shared\Services\Http\DTOs\HttpRequestDto;
use App\Shared\Services\Http\DTOs\HttpResponseDto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class IntegrationConnectionService
{
    public function __construct(
        private readonly IntegrationRepository $integrationRepository,
        private readonly IntegrationConnectionLogRepository $connectionLogRepository,
        private readonly ApiClientService $apiClientService,
    ) {}

    /**
     * @return array{integration: Integration, response: array<string, mixed>, log: IntegrationConnectionLog}
     */
    public function testConnection(string $identifier, User $actor): array
    {
        $integration = $this->requireRestCapable($identifier);

        return $this->executeAndPersist(
            integration: $integration,
            actor: $actor,
            requestType: ConnectionRequestType::ConnectionTest,
            overrides: [
                'method' => 'GET',
                'path' => $integration->health_check_path ?: '/',
                'apply_auth' => false,
                'body' => null,
            ],
            updateHealth: true,
        );
    }

    /**
     * @return array{integration: Integration, response: array<string, mixed>, log: IntegrationConnectionLog}
     */
    public function testAuthentication(string $identifier, User $actor): array
    {
        $integration = $this->requireRestCapable($identifier);

        if (empty($integration->credentials)) {
            throw new ApiException('Configure credentials before running an authentication test.', 422);
        }

        return $this->executeAndPersist(
            integration: $integration,
            actor: $actor,
            requestType: ConnectionRequestType::AuthenticationTest,
            overrides: [
                'method' => 'GET',
                'path' => $integration->health_check_path ?: '/',
                'apply_auth' => true,
                'body' => null,
            ],
            updateHealth: true,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, UploadedFile>  $files
     * @return array{integration: Integration, response: array<string, mixed>, log: IntegrationConnectionLog}
     */
    public function executeRequest(string $identifier, array $payload, User $actor, array $files = []): array
    {
        $integration = $this->requireRestCapable($identifier);
        $method = strtoupper((string) ($payload['method'] ?? 'GET'));
        $asDownload = (bool) ($payload['as_download'] ?? false);
        $requestType = $files !== []
            ? ConnectionRequestType::Upload
            : ($asDownload ? ConnectionRequestType::Download : ConnectionRequestType::Request);

        $body = $payload['body'] ?? null;
        if (is_string($body) && $body !== '') {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $body = $decoded;
            }
        }

        return $this->executeAndPersist(
            integration: $integration,
            actor: $actor,
            requestType: $requestType,
            overrides: [
                'method' => $method,
                'path' => (string) ($payload['path'] ?? '/'),
                'headers' => (array) ($payload['headers'] ?? []),
                'query' => (array) ($payload['query'] ?? []),
                'body' => $body,
                'files' => $files,
                'apply_auth' => (bool) ($payload['apply_auth'] ?? true),
                'as_multipart' => $files !== [],
                'as_download' => $asDownload,
                'timeout' => $payload['timeout'] ?? null,
                'retry_attempts' => $payload['retry_attempts'] ?? null,
            ],
            updateHealth: false,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateConfiguration(string $identifier, array $data, User $actor): Integration
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Integration {
            $integration = $this->integrationRepository->findByIdentifierOrFail($identifier);

            $payload = array_intersect_key($data, array_flip([
                'base_url',
                'api_version',
                'timeout',
                'retry_attempts',
                'default_headers',
                'default_query',
                'rate_limit_per_minute',
                'health_check_path',
                'authentication_type',
            ]));

            if (array_key_exists('credentials', $data) && is_array($data['credentials'])) {
                $existing = is_array($integration->credentials) ? $integration->credentials : [];
                $incoming = $data['credentials'];
                foreach ($incoming as $key => $value) {
                    if ($value === null || $value === '') {
                        unset($existing[$key]);
                        continue;
                    }
                    $existing[$key] = $value;
                }
                $payload['credentials'] = $existing === [] ? null : $existing;
            }

            if (array_key_exists('clear_credentials', $data) && $data['clear_credentials']) {
                $payload['credentials'] = null;
            }

            foreach (['base_url', 'api_version', 'health_check_path'] as $nullable) {
                if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                    $payload[$nullable] = null;
                }
            }

            if (array_key_exists('rate_limit_per_minute', $payload) && $payload['rate_limit_per_minute'] === '') {
                $payload['rate_limit_per_minute'] = null;
            }

            $payload['updated_by'] = $actor->id;
            $updated = $this->integrationRepository->updateIntegration($integration, $payload);
            event(new IntegrationConfigurationUpdated($updated, $actor));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listHistory(string $identifier, array $filters = []): LengthAwarePaginator
    {
        $integration = $this->integrationRepository->findByIdentifierOrFail($identifier);

        return $this->connectionLogRepository->paginateForIntegration($integration->id, $filters);
    }

    public function showHistoryEntry(string $identifier, string $logUuid): IntegrationConnectionLog
    {
        $integration = $this->integrationRepository->findByIdentifierOrFail($identifier);
        $log = $this->connectionLogRepository->findByUuidOrFail($logUuid);

        if ($log->integration_id !== $integration->id) {
            abort(404, 'Connection history entry not found.');
        }

        return $log->load(['actor:id,uuid,full_name,email']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{integration: Integration, response: array<string, mixed>, log: IntegrationConnectionLog}
     */
    protected function executeAndPersist(
        Integration $integration,
        User $actor,
        ConnectionRequestType $requestType,
        array $overrides,
        bool $updateHealth,
    ): array {
        if (blank($integration->base_url)) {
            throw new ApiException('Integration base URL is required before connecting.', 422);
        }

        $connection = $this->toConnectionContext($integration);
        $requestDto = $this->apiClientService->connectionManager()->buildRequestDto($connection, $overrides);
        $response = $this->apiClientService->send($requestDto);

        $log = $this->persistLog($integration, $actor, $requestType, $requestDto, $response);
        event(new IntegrationConnectionExecuted($integration, $log, $actor));

        if ($updateHealth) {
            $health = $this->resolveHealth($response, $requestType);
            $this->integrationRepository->updateIntegration($integration, [
                'health_status' => $health->value,
                'last_health_check' => now(),
                'updated_by' => $actor->id,
            ]);
            $integration = $integration->refresh()->load([
                'company:id,uuid,company_name,status',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ]);
        }

        return [
            'integration' => $integration,
            'response' => $response->toArray(),
            'log' => $log,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function toConnectionContext(Integration $integration): array
    {
        return [
            'base_url' => $integration->base_url,
            'default_headers' => $integration->default_headers ?? [],
            'default_query' => $integration->default_query ?? [],
            'timeout' => $integration->timeout,
            'retry_attempts' => $integration->retry_attempts,
            'rate_limit_per_minute' => $integration->rate_limit_per_minute,
            'rate_limit_key' => 'integration:'.$integration->id,
            'health_check_path' => $integration->health_check_path,
            'authentication_type' => $integration->authentication_type?->value ?? $integration->authentication_type,
            'credentials' => is_array($integration->credentials) ? $integration->credentials : [],
            'context' => [
                'integration_id' => $integration->id,
                'integration_uuid' => $integration->uuid,
            ],
        ];
    }

    protected function persistLog(
        Integration $integration,
        User $actor,
        ConnectionRequestType $requestType,
        HttpRequestDto $request,
        HttpResponseDto $response,
    ): IntegrationConnectionLog {
        $bodyForLog = $request->body;
        if (is_array($bodyForLog)) {
            $bodyForLog = json_encode($this->redactSensitive($bodyForLog));
        }

        $responseBody = $response->rawBody;
        if ($responseBody === null && is_array($response->body)) {
            $responseBody = json_encode($response->body);
        }
        if (is_string($responseBody) && strlen($responseBody) > 50000) {
            $responseBody = substr($responseBody, 0, 50000).'...[truncated]';
        }

        return $this->connectionLogRepository->createLog([
            'integration_id' => $integration->id,
            'company_id' => $integration->company_id,
            'request_type' => $requestType->value,
            'method' => $request->method(),
            'url' => $request->url,
            'request_headers' => $this->maskHeaders($request->headers),
            'request_query' => $this->redactSensitive($request->query),
            'request_body' => is_string($bodyForLog) ? $bodyForLog : null,
            'response_status' => $response->statusCode > 0 ? $response->statusCode : null,
            'response_headers' => $response->headers,
            'response_body' => $responseBody,
            'duration_ms' => $response->durationMs,
            'attempts' => $response->attempts,
            'success' => $response->successful,
            'error_message' => $response->error,
            'triggered_by' => $actor->id,
        ]);
    }

    protected function resolveHealth(HttpResponseDto $response, ConnectionRequestType $type): IntegrationHealthStatus
    {
        if (! $response->successful) {
            if ($type === ConnectionRequestType::AuthenticationTest && in_array($response->statusCode, [401, 403], true)) {
                return IntegrationHealthStatus::Unhealthy;
            }

            return $response->statusCode >= 500 || $response->statusCode === 0
                ? IntegrationHealthStatus::Unhealthy
                : IntegrationHealthStatus::Degraded;
        }

        return IntegrationHealthStatus::Healthy;
    }

    protected function requireRestCapable(string $identifier): Integration
    {
        $integration = $this->integrationRepository->findByIdentifierOrFail($identifier);
        $type = $integration->type?->value ?? (string) $integration->type;

        if (! in_array($type, ['rest_api', 'graphql', 'webhook'], true)) {
            throw new ApiException('The API Connection Engine currently supports REST, GraphQL, and Webhook integrations.', 422);
        }

        return $integration;
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    protected function maskHeaders(array $headers): array
    {
        $masked = [];
        foreach ($headers as $key => $value) {
            $lower = strtolower((string) $key);
            if (in_array($lower, ['authorization', 'x-api-key', 'api-key', 'cookie', 'set-cookie', 'x-auth-token'], true)) {
                $masked[$key] = '***MASKED***';
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function redactSensitive(array $payload): array
    {
        $sensitive = ['password', 'token', 'api_key', 'secret', 'client_secret', 'access_token', 'refresh_token', 'bearer_token', 'jwt_token', 'oauth_access_token'];
        $redacted = [];

        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $redacted[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactSensitive($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }
}
