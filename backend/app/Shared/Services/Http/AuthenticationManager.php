<?php

namespace App\Shared\Services\Http;

use App\Shared\Exceptions\ApiException;

class AuthenticationManager
{
    /**
     * Build authentication headers and query params for an outbound request.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{headers: array<string, string>, query: array<string, mixed>}
     */
    public function apply(string $type, array $credentials = []): array
    {
        return match ($type) {
            'api_key' => $this->applyApiKey($credentials),
            'bearer_token' => $this->applyBearer($credentials),
            'basic_auth' => $this->applyBasic($credentials),
            'jwt' => $this->applyJwt($credentials),
            'oauth2' => $this->applyOAuth2($credentials),
            default => throw new ApiException('Unsupported authentication type.', 422),
        };
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{headers: array<string, string>, query: array<string, mixed>}
     */
    protected function applyApiKey(array $credentials): array
    {
        $key = (string) ($credentials['api_key'] ?? '');
        if ($key === '') {
            throw new ApiException('API key credentials are not configured.', 422);
        }

        $header = (string) ($credentials['api_key_header'] ?? 'X-API-Key');
        $location = strtolower((string) ($credentials['api_key_location'] ?? 'header'));

        if ($location === 'query') {
            $queryName = (string) ($credentials['api_key_query'] ?? 'api_key');

            return ['headers' => [], 'query' => [$queryName => $key]];
        }

        return ['headers' => [$header => $key], 'query' => []];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{headers: array<string, string>, query: array<string, mixed>}
     */
    protected function applyBearer(array $credentials): array
    {
        $token = (string) ($credentials['bearer_token'] ?? $credentials['access_token'] ?? '');
        if ($token === '') {
            throw new ApiException('Bearer token credentials are not configured.', 422);
        }

        return ['headers' => ['Authorization' => 'Bearer '.$token], 'query' => []];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{headers: array<string, string>, query: array<string, mixed>}
     */
    protected function applyBasic(array $credentials): array
    {
        $username = (string) ($credentials['username'] ?? '');
        $password = (string) ($credentials['password'] ?? '');

        if ($username === '') {
            throw new ApiException('Basic auth credentials are not configured.', 422);
        }

        $encoded = base64_encode($username.':'.$password);

        return ['headers' => ['Authorization' => 'Basic '.$encoded], 'query' => []];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{headers: array<string, string>, query: array<string, mixed>}
     */
    protected function applyJwt(array $credentials): array
    {
        $token = (string) ($credentials['jwt_token'] ?? $credentials['token'] ?? '');
        if ($token === '') {
            throw new ApiException('JWT credentials are not configured.', 422);
        }

        $header = (string) ($credentials['jwt_header'] ?? 'Authorization');
        $prefix = (string) ($credentials['jwt_prefix'] ?? 'Bearer');

        return [
            'headers' => [$header => trim($prefix.' '.$token)],
            'query' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{headers: array<string, string>, query: array<string, mixed>}
     */
    protected function applyOAuth2(array $credentials): array
    {
        $token = (string) ($credentials['oauth_access_token'] ?? $credentials['access_token'] ?? '');
        if ($token === '') {
            throw new ApiException('OAuth2 access token is not configured.', 422);
        }

        $type = (string) ($credentials['oauth_token_type'] ?? 'Bearer');

        return ['headers' => ['Authorization' => trim($type.' '.$token)], 'query' => []];
    }
}
