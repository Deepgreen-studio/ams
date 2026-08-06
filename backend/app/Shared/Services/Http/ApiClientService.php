<?php

namespace App\Shared\Services\Http;

use App\Shared\Services\Http\DTOs\HttpRequestDto;
use App\Shared\Services\Http\DTOs\HttpResponseDto;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;

/**
 * Enterprise outbound HTTP engine.
 *
 * All external API connections MUST go through this service.
 * Domain modules must never call Http:: or Guzzle directly.
 */
class ApiClientService
{
    public function __construct(
        private readonly ConnectionManager $connectionManager,
        private readonly AuthenticationManager $authenticationManager,
        private readonly RequestBuilder $requestBuilder,
        private readonly ResponseParser $responseParser,
        private readonly RetryManager $retryManager,
        private readonly TimeoutManager $timeoutManager,
        private readonly RateLimitManager $rateLimitManager,
    ) {}

    public function connectionManager(): ConnectionManager
    {
        return $this->connectionManager;
    }

    public function authenticationManager(): AuthenticationManager
    {
        return $this->authenticationManager;
    }

    /**
     * @param  array<string, mixed>  $connection
     * @param  array<string, mixed>  $overrides
     */
    public function sendFromConnection(array $connection, array $overrides = []): HttpResponseDto
    {
        return $this->send($this->connectionManager->buildRequestDto($connection, $overrides));
    }

    public function send(HttpRequestDto $request): HttpResponseDto
    {
        $this->rateLimitManager->assertAllowed($request->rateLimitKey, $request->rateLimitPerMinute);

        $built = $this->requestBuilder->build(
            $request->method(),
            $request->url,
            $request->headers,
            $request->query,
            $request->body,
            $request->files,
            $request->asMultipart || $request->files !== [],
        );

        /** @var PendingRequest $pending */
        $pending = $built['pending'];
        $timeout = $this->timeoutManager->resolve($request->timeout);
        $pending = $pending->timeout($timeout)->connectTimeout(min(10, $timeout));

        $started = hrtime(true);
        $result = $this->retryManager->execute(
            $pending,
            function (PendingRequest $client) use ($built) {
                $method = strtolower((string) $built['method']);
                $url = (string) $built['url'];
                $options = (array) $built['options'];
                $payload = $built['payload'];

                if (in_array($method, ['get', 'head', 'delete'], true) && $payload === null) {
                    return $client->send($method, $url, $options);
                }

                if ($built['multipart'] === true) {
                    return $client->send($method, $url, $options);
                }

                return $client->send($method, $url, array_merge($options, [
                    'json' => is_array($payload) ? $payload : null,
                    'body' => is_string($payload) ? $payload : null,
                ]));
            },
            $request->retryAttempts ?? 1
        );

        $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
        $this->rateLimitManager->hit($request->rateLimitKey, $request->rateLimitPerMinute);

        if ($result['exception'] !== null || $result['response'] === null) {
            return $this->responseParser->fromException(
                $result['exception'] ?? new \RuntimeException('HTTP request failed without a response.'),
                $durationMs,
                (int) $result['attempts']
            );
        }

        return $this->responseParser->parse(
            $result['response'],
            $durationMs,
            (int) $result['attempts'],
            $request->asDownload
        );
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     */
    public function get(string $url, array $headers = [], array $query = [], ?int $timeout = null, ?int $retries = null): HttpResponseDto
    {
        return $this->send(new HttpRequestDto('GET', $url, $headers, $query, timeout: $timeout, retryAttempts: $retries));
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|string|null  $body
     */
    public function post(string $url, array|string|null $body = null, array $headers = [], array $query = [], ?int $timeout = null, ?int $retries = null): HttpResponseDto
    {
        return $this->send(new HttpRequestDto('POST', $url, $headers, $query, $body, timeout: $timeout, retryAttempts: $retries));
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|string|null  $body
     */
    public function put(string $url, array|string|null $body = null, array $headers = [], array $query = [], ?int $timeout = null, ?int $retries = null): HttpResponseDto
    {
        return $this->send(new HttpRequestDto('PUT', $url, $headers, $query, $body, timeout: $timeout, retryAttempts: $retries));
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|string|null  $body
     */
    public function patch(string $url, array|string|null $body = null, array $headers = [], array $query = [], ?int $timeout = null, ?int $retries = null): HttpResponseDto
    {
        return $this->send(new HttpRequestDto('PATCH', $url, $headers, $query, $body, timeout: $timeout, retryAttempts: $retries));
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|string|null  $body
     */
    public function delete(string $url, array|string|null $body = null, array $headers = [], array $query = [], ?int $timeout = null, ?int $retries = null): HttpResponseDto
    {
        return $this->send(new HttpRequestDto('DELETE', $url, $headers, $query, $body, timeout: $timeout, retryAttempts: $retries));
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     * @param  array<string, UploadedFile|string>  $files
     * @param  array<string, mixed>|null  $fields
     */
    public function upload(
        string $url,
        array $files,
        ?array $fields = null,
        string $method = 'POST',
        array $headers = [],
        array $query = [],
        ?int $timeout = null,
        ?int $retries = null,
    ): HttpResponseDto {
        return $this->send(new HttpRequestDto(
            method: $method,
            url: $url,
            headers: $headers,
            query: $query,
            body: $fields,
            files: $files,
            timeout: $timeout,
            retryAttempts: $retries,
            asMultipart: true,
        ));
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     */
    public function download(
        string $url,
        array $headers = [],
        array $query = [],
        string $method = 'GET',
        ?int $timeout = null,
        ?int $retries = null,
    ): HttpResponseDto {
        return $this->send(new HttpRequestDto(
            method: $method,
            url: $url,
            headers: $headers,
            query: $query,
            timeout: $timeout,
            retryAttempts: $retries,
            asDownload: true,
        ));
    }
}
