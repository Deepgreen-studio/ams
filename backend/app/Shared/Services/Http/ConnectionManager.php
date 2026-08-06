<?php

namespace App\Shared\Services\Http;

use App\Shared\Services\Http\DTOs\HttpRequestDto;

class ConnectionManager
{
    public function __construct(
        private readonly AuthenticationManager $authenticationManager
    ) {}

    /**
     * Resolve a fully qualified URL from a base URL and relative path.
     */
    public function resolveUrl(?string $baseUrl, string $path = ''): string
    {
        $path = trim($path);
        if ($path !== '' && preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        $base = rtrim((string) $baseUrl, '/');
        if ($base === '') {
            throw new \InvalidArgumentException('Base URL is required for this connection.');
        }

        if ($path === '' || $path === '/') {
            return $base;
        }

        return $base.'/'.ltrim($path, '/');
    }

    /**
     * Merge connection defaults with request overrides and applied authentication.
     *
     * @param  array<string, mixed>  $connection
     * @param  array<string, mixed>  $overrides
     */
    public function buildRequestDto(array $connection, array $overrides = []): HttpRequestDto
    {
        $method = strtoupper((string) ($overrides['method'] ?? 'GET'));
        $path = (string) ($overrides['path'] ?? $overrides['url'] ?? ($connection['health_check_path'] ?? ''));
        $url = $this->resolveUrl($connection['base_url'] ?? null, $path);

        $headers = array_merge(
            (array) ($connection['default_headers'] ?? []),
            (array) ($overrides['headers'] ?? [])
        );

        $query = array_merge(
            (array) ($connection['default_query'] ?? []),
            (array) ($overrides['query'] ?? [])
        );

        $applyAuth = (bool) ($overrides['apply_auth'] ?? true);
        if ($applyAuth && ! empty($connection['authentication_type'])) {
            $auth = $this->authenticationManager->apply(
                (string) $connection['authentication_type'],
                (array) ($connection['credentials'] ?? [])
            );
            $headers = array_merge($headers, $auth['headers']);
            $query = array_merge($query, $auth['query']);
        }

        return new HttpRequestDto(
            method: $method,
            url: $url,
            headers: $this->stringifyHeaders($headers),
            query: $query,
            body: $overrides['body'] ?? null,
            files: (array) ($overrides['files'] ?? []),
            timeout: isset($overrides['timeout']) ? (int) $overrides['timeout'] : ($connection['timeout'] ?? null),
            retryAttempts: isset($overrides['retry_attempts'])
                ? (int) $overrides['retry_attempts']
                : ($connection['retry_attempts'] ?? null),
            asMultipart: (bool) ($overrides['as_multipart'] ?? false),
            asDownload: (bool) ($overrides['as_download'] ?? false),
            rateLimitKey: $connection['rate_limit_key'] ?? null,
            rateLimitPerMinute: isset($connection['rate_limit_per_minute'])
                ? (int) $connection['rate_limit_per_minute']
                : null,
            context: (array) ($connection['context'] ?? []),
        );
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, string>
     */
    protected function stringifyHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $result[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        return $result;
    }
}
