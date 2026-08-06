<?php

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Events\ApiLogged;
use App\Domains\Audit\Models\ApiLog;
use App\Domains\Audit\Repositories\ApiLogRepository;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ApiLogService
{
    public function __construct(
        private readonly ApiLogRepository $apiLogRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->apiLogRepository->paginateFiltered($filters);
    }

    public function show(string $identifier): ApiLog
    {
        return $this->apiLogRepository->findByIdentifierOrFail($identifier);
    }

    /**
     * @param  array<string, mixed>|null  $requestPayload
     * @param  array<string, mixed>|null  $responsePayload
     */
    public function record(
        string $endpoint,
        string $method,
        ?array $requestPayload,
        ?array $responsePayload,
        ?int $responseCode,
        int $durationMs,
        ?User $user = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): ApiLog {
        /** @var ApiLog $log */
        $log = $this->apiLogRepository->create([
            'endpoint' => Str::limit($endpoint, 500, ''),
            'method' => strtoupper($method),
            'request' => $this->sanitizePayload($requestPayload),
            'response' => $this->sanitizePayload($responsePayload),
            'response_code' => $responseCode,
            'duration' => max(0, $durationMs),
            'user_id' => $user?->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        event(new ApiLogged($log));

        return $log;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    protected function sanitizePayload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $sensitive = ['password', 'password_confirmation', 'current_password', 'token', 'smtp_password', 'secret'];

        foreach ($sensitive as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '[redacted]';
            }
        }

        $encoded = json_encode($payload);
        if ($encoded !== false && strlen($encoded) > 20000) {
            return ['_truncated' => true, 'size' => strlen($encoded)];
        }

        return $payload;
    }
}
