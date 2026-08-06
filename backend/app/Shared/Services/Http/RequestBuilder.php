<?php

namespace App\Shared\Services\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class RequestBuilder
{
    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|string|null  $body
     * @param  array<string, mixed>  $files
     */
    public function build(
        string $method,
        string $url,
        array $headers = [],
        array $query = [],
        array|string|null $body = null,
        array $files = [],
        bool $asMultipart = false,
    ): array {
        $pending = Http::withHeaders($headers)->acceptJson();

        $options = [
            'query' => $query,
        ];

        $hasFiles = $files !== [] || $asMultipart;

        if ($hasFiles) {
            $pending = $this->attachFiles($pending, $files);
            if (is_array($body)) {
                foreach ($body as $key => $value) {
                    if (is_scalar($value) || $value === null) {
                        $pending = $pending->attach((string) $key, (string) ($value ?? ''));
                    }
                }
            }

            return [
                'pending' => $pending,
                'method' => strtoupper($method),
                'url' => $url,
                'options' => $options,
                'payload' => null,
                'multipart' => true,
            ];
        }

        $payload = $body;
        if (is_array($body)) {
            $pending = $pending->asJson();
            $payload = $body;
        } elseif (is_string($body) && $body !== '') {
            $pending = $pending->withBody($body, $headers['Content-Type'] ?? 'application/json');
            $payload = null;
        }

        return [
            'pending' => $pending,
            'method' => strtoupper($method),
            'url' => $url,
            'options' => $options,
            'payload' => $payload,
            'multipart' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $files
     */
    protected function attachFiles(PendingRequest $pending, array $files): PendingRequest
    {
        foreach ($files as $field => $file) {
            if ($file instanceof UploadedFile) {
                $pending = $pending->attach(
                    (string) $field,
                    fopen($file->getRealPath(), 'r'),
                    $file->getClientOriginalName()
                );
            } elseif (is_string($file) && is_file($file)) {
                $pending = $pending->attach(
                    (string) $field,
                    fopen($file, 'r'),
                    basename($file)
                );
            }
        }

        return $pending;
    }
}
