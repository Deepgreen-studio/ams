<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Integrations\Enums\IntegrationAuthenticationType;
use App\Domains\Integrations\Enums\IntegrationStatus;
use App\Domains\Integrations\Models\Integration;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Services\Http\ApiClientService;
use App\Shared\Services\Http\DTOs\HttpRequestDto;
use Illuminate\Support\Carbon;

class OAuthTokenService
{
    public function __construct(
        private readonly ApiClientService $apiClientService,
        private readonly CredentialVault $credentialVault,
    ) {}

    public function isDue(Integration $integration): bool
    {
        if ($integration->authentication_type !== IntegrationAuthenticationType::OAuth2) {
            return false;
        }

        if ($integration->status !== IntegrationStatus::Active) {
            return false;
        }

        $credentials = is_array($integration->credentials) ? $integration->credentials : [];
        if (! $this->canRefresh($credentials)) {
            return false;
        }

        $expiresAt = $credentials['oauth_expires_at'] ?? null;
        if (! is_string($expiresAt) || $expiresAt === '') {
            return true;
        }

        $skew = (int) config('integration_connectors.oauth_refresh_skew_seconds', 300);

        return Carbon::parse($expiresAt)->lte(now()->addSeconds($skew));
    }

    public function refresh(Integration $integration, ?User $actor = null): Integration
    {
        if ($integration->authentication_type !== IntegrationAuthenticationType::OAuth2) {
            throw new ApiException('This integration does not use OAuth2.', 422);
        }

        $credentials = is_array($integration->credentials) ? $integration->credentials : [];
        if (! $this->canRefresh($credentials)) {
            throw new ApiException('OAuth2 refresh requires token_url, client_id, client_secret, and oauth_refresh_token.', 422);
        }

        $response = $this->apiClientService->send(new HttpRequestDto(
            method: 'POST',
            url: (string) $credentials['token_url'],
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
            body: [
                'grant_type' => 'refresh_token',
                'refresh_token' => (string) ($credentials['oauth_refresh_token'] ?? $credentials['refresh_token']),
                'client_id' => (string) $credentials['client_id'],
                'client_secret' => (string) $credentials['client_secret'],
            ],
            timeout: (int) ($integration->timeout ?: 30),
            retryAttempts: 1,
            context: [
                'integration_id' => $integration->id,
                'purpose' => 'oauth_refresh',
            ],
        ));

        $body = is_array($response->body) ? $response->body : [];
        $accessToken = (string) ($body['access_token'] ?? '');

        if (! $response->successful || $accessToken === '') {
            throw new ApiException('OAuth2 token endpoint did not return an access token.', 422);
        }

        $expiresIn = max(60, (int) ($body['expires_in'] ?? 3600));
        $updates = [
            'oauth_access_token' => $accessToken,
            'oauth_token_type' => (string) ($body['token_type'] ?? 'Bearer'),
            'oauth_expires_at' => now()->addSeconds($expiresIn)->toIso8601String(),
        ];

        if (filled($body['refresh_token'] ?? null)) {
            $updates['oauth_refresh_token'] = (string) $body['refresh_token'];
        }

        return $this->credentialVault->merge($integration, $updates, $actor);
    }

    /**
     * @param array<string, mixed> $credentials
     */
    private function canRefresh(array $credentials): bool
    {
        return filled($credentials['token_url'] ?? null)
            && filled($credentials['client_id'] ?? null)
            && filled($credentials['client_secret'] ?? null)
            && filled($credentials['oauth_refresh_token'] ?? $credentials['refresh_token'] ?? null);
    }
}
