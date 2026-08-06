<?php

namespace App\Domains\Audit\Middleware;

use App\Domains\Audit\Services\ApiLogService;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    public function __construct(
        private readonly ApiLogService $apiLogService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldLog($request)) {
            return $response;
        }

        try {
            $duration = (int) round((microtime(true) - $started) * 1000);
            /** @var User|null $user */
            $user = $request->user();

            $responsePayload = null;
            $content = $response->getContent();
            if (is_string($content) && $content !== '') {
                $decoded = json_decode($content, true);
                $responsePayload = is_array($decoded) ? $decoded : ['_raw' => true];
            }

            $this->apiLogService->record(
                endpoint: '/'.$request->path(),
                method: $request->method(),
                requestPayload: $request->except(['password', 'password_confirmation', 'current_password', 'smtp_password']),
                responsePayload: $responsePayload,
                responseCode: $response->getStatusCode(),
                durationMs: $duration,
                user: $user,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );
        } catch (\Throwable) {
            // Never break the request pipeline because logging failed.
        }

        return $response;
    }

    protected function shouldLog(Request $request): bool
    {
        if (! config('audit.log_api_requests', true)) {
            return false;
        }

        if (app()->environment('testing') && ! config('audit.log_api_in_tests', false)) {
            return false;
        }

        if (! $request->is('api/*')) {
            return false;
        }

        // Avoid recursive / noisy endpoints.
        if ($request->is('api/v1/api-logs*') || $request->is('api/v1/activity-logs*') || $request->is('api/v1/health')) {
            return false;
        }

        return true;
    }
}
