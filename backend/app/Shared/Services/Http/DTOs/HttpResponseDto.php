<?php

namespace App\Shared\Services\Http\DTOs;

final class HttpResponseDto
{
    /**
     * @param  array<string, mixed>  $headers
     */
    public function __construct(
        public readonly bool $successful,
        public readonly int $statusCode,
        public readonly array $headers,
        public readonly mixed $body,
        public readonly ?string $rawBody,
        public readonly int $durationMs,
        public readonly int $attempts,
        public readonly ?string $error = null,
        public readonly bool $isBinary = false,
        public readonly ?string $contentType = null,
        public readonly ?string $downloadPath = null,
        public readonly ?string $downloadFilename = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'status_code' => $this->statusCode,
            'headers' => $this->headers,
            'body' => $this->body,
            'raw_body' => $this->rawBody,
            'duration_ms' => $this->durationMs,
            'attempts' => $this->attempts,
            'error' => $this->error,
            'is_binary' => $this->isBinary,
            'content_type' => $this->contentType,
            'download_path' => $this->downloadPath,
            'download_filename' => $this->downloadFilename,
        ];
    }
}
