<?php

namespace App\Shared\Exceptions;

use App\Domains\Audit\Services\ErrorLogService;
use App\Shared\Helpers\ValidationFormatter;
use App\Shared\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler
{
    public function shouldRenderJson(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * Persist selected exceptions into the Audit error log store.
     * Returns null so Laravel continues default reporting.
     */
    public function report(Throwable $e): ?bool
    {
        if (! config('audit.log_exceptions', true)) {
            return null;
        }

        if ($this->shouldSkipPersistence($e)) {
            return null;
        }

        try {
            app(ErrorLogService::class)->capture(
                $e,
                request(),
                auth()->user()
            );
        } catch (Throwable) {
            // Swallow persistence failures to avoid report loops.
        }

        return null;
    }

    public function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $this->shouldRenderJson($request)) {
            return null;
        }

        if ($e instanceof ApiException) {
            return $e->render();
        }

        if ($e instanceof ValidationException) {
            return ApiResponse::validationError(
                ValidationFormatter::format($e->errors())
            );
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::unauthorized();
        }

        if ($e instanceof AuthorizationException) {
            return ApiResponse::forbidden($e->getMessage() ?: 'Forbidden');
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return ApiResponse::notFound();
        }

        if ($e instanceof HttpExceptionInterface) {
            return ApiResponse::error(
                $e->getMessage() ?: 'Unexpected Error',
                $e->getStatusCode()
            );
        }

        $message = config('app.debug')
            ? ($e->getMessage() ?: 'Unexpected Error')
            : 'Unexpected Error';

        return ApiResponse::error($message, 500);
    }

    protected function shouldSkipPersistence(Throwable $e): bool
    {
        return $e instanceof ValidationException
            || $e instanceof AuthenticationException
            || $e instanceof AuthorizationException
            || $e instanceof ModelNotFoundException
            || $e instanceof NotFoundHttpException
            || ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500)
            || $e instanceof ApiException;
    }
}
