<?php

namespace App\Shared\Services\Http;

use App\Shared\Services\Http\DTOs\HttpResponseDto;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResponseParser
{
    public function parse(Response $response, int $durationMs, int $attempts, bool $asDownload = false): HttpResponseDto
    {
        $contentType = (string) ($response->header('Content-Type') ?? '');
        $raw = $response->body();
        $isBinary = $asDownload || $this->looksBinary($contentType, $raw);

        $downloadPath = null;
        $downloadFilename = null;
        $body = null;

        if ($isBinary) {
            $downloadFilename = $this->resolveFilename($response);
            $downloadPath = 'integration-downloads/'.Str::uuid().'_'.$downloadFilename;
            Storage::disk('local')->put($downloadPath, $raw);
            $body = [
                'stored' => true,
                'path' => $downloadPath,
                'filename' => $downloadFilename,
                'size' => strlen($raw),
            ];
            $rawForDto = null;
        } else {
            $json = $response->json();
            $body = $json ?? $raw;
            $rawForDto = $this->truncate($raw);
        }

        return new HttpResponseDto(
            successful: $response->successful(),
            statusCode: $response->status(),
            headers: $this->normalizeHeaders($response->headers()),
            body: $body,
            rawBody: $rawForDto,
            durationMs: $durationMs,
            attempts: $attempts,
            error: $response->failed() ? $this->truncate($response->body(), 1000) : null,
            isBinary: $isBinary,
            contentType: $contentType !== '' ? $contentType : null,
            downloadPath: $downloadPath,
            downloadFilename: $downloadFilename,
        );
    }

    public function fromException(\Throwable $exception, int $durationMs, int $attempts): HttpResponseDto
    {
        return new HttpResponseDto(
            successful: false,
            statusCode: 0,
            headers: [],
            body: null,
            rawBody: null,
            durationMs: $durationMs,
            attempts: $attempts,
            error: $exception->getMessage(),
        );
    }

    /**
     * @param  array<string, array<int, string>|string>  $headers
     * @return array<string, string>
     */
    protected function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[(string) $key] = is_array($value) ? implode(', ', $value) : (string) $value;
        }

        return $normalized;
    }

    protected function looksBinary(string $contentType, string $raw): bool
    {
        $type = strtolower($contentType);
        if (str_contains($type, 'json') || str_contains($type, 'text/') || str_contains($type, 'xml') || str_contains($type, 'html')) {
            return false;
        }

        if ($type !== '' && (
            str_contains($type, 'octet-stream')
            || str_contains($type, 'application/pdf')
            || str_contains($type, 'image/')
            || str_contains($type, 'zip')
            || str_contains($type, 'multipart')
        )) {
            return true;
        }

        return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', substr($raw, 0, 512)) === 1;
    }

    protected function resolveFilename(Response $response): string
    {
        $disposition = (string) ($response->header('Content-Disposition') ?? '');
        if (preg_match('/filename=\"?([^\";]+)\"?/i', $disposition, $matches) === 1) {
            return basename($matches[1]);
        }

        return 'download.bin';
    }

    protected function truncate(?string $value, int $limit = 50000): ?string
    {
        if ($value === null) {
            return null;
        }

        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit).'...[truncated]';
    }
}
