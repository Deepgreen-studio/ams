<?php

namespace App\Shared\Services\Http\DTOs;

final class HttpRequestDto
{
    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|string|null  $body
     * @param  array<string, mixed>  $files  field => UploadedFile|string path
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly array $headers = [],
        public readonly array $query = [],
        public readonly array|string|null $body = null,
        public readonly array $files = [],
        public readonly ?int $timeout = null,
        public readonly ?int $retryAttempts = null,
        public readonly bool $asMultipart = false,
        public readonly bool $asDownload = false,
        public readonly ?string $rateLimitKey = null,
        public readonly ?int $rateLimitPerMinute = null,
        public readonly array $context = [],
    ) {}

    public function method(): string
    {
        return strtoupper($this->method);
    }
}
