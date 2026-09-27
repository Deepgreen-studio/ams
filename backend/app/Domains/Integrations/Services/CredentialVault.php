<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Integrations\Events\IntegrationConfigurationUpdated;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Models\User;

class CredentialVault
{
    /**
     * Field names accepted by the vault. Values are encrypted and never returned by the API.
     *
     * @var list<string>
     */
    public const ALLOWED_KEYS = [
        'api_key',
        'api_key_header',
        'api_key_location',
        'api_key_query',
        'bearer_token',
        'username',
        'password',
        'jwt_token',
        'jwt_header',
        'jwt_prefix',
        'token',
        'access_token',
        'oauth_access_token',
        'oauth_refresh_token',
        'oauth_token_type',
        'oauth_expires_at',
        'refresh_token',
        'client_id',
        'client_secret',
        'token_url',
    ];

    public function __construct(
        private readonly IntegrationRepository $integrationRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function profile(Integration $integration): array
    {
        $credentials = is_array($integration->credentials) ? $integration->credentials : [];
        $keys = [];
        foreach ($credentials as $key => $value) {
            if ($value !== null && $value !== '') {
                $keys[] = (string) $key;
            }
        }

        return [
            'configured' => $keys !== [],
            'keys' => $keys,
            'oauth_expires_at' => $credentials['oauth_expires_at'] ?? null,
            'refresh_available' => filled($credentials['token_url'] ?? null)
                && filled($credentials['client_id'] ?? null)
                && filled($credentials['client_secret'] ?? null)
                && filled($credentials['oauth_refresh_token'] ?? $credentials['refresh_token'] ?? null),
        ];
    }

    /**
     * Merge non-empty credential fields. Blank values keep the stored secret.
     *
     * @param array<string, mixed> $incoming
     */
    public function merge(Integration $integration, array $incoming, ?User $actor = null): Integration
    {
        $current = is_array($integration->credentials) ? $integration->credentials : [];

        foreach ($incoming as $key => $value) {
            if (! is_string($key) || ! in_array($key, self::ALLOWED_KEYS, true)) {
                continue;
            }

            if ($value === null || $value === '' || is_array($value)) {
                continue;
            }

            $current[$key] = (string) $value;
        }

        $updated = $this->integrationRepository->updateCredentials($integration, $current, $actor?->id);

        if ($actor !== null) {
            event(new IntegrationConfigurationUpdated($updated, $actor));
        }

        return $updated;
    }
}
