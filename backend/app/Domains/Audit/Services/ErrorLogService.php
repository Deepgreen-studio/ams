<?php

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Events\ErrorCaptured;
use App\Domains\Audit\Models\ErrorLog;
use App\Domains\Audit\Repositories\ErrorRepository;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class ErrorLogService
{
    public function __construct(
        private readonly ErrorRepository $errorRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->errorRepository->paginateFiltered($filters);
    }

    public function show(string $identifier): ErrorLog
    {
        return $this->errorRepository->findByIdentifierOrFail($identifier);
    }

    public function capture(Throwable $exception, ?Request $request = null, ?User $user = null): ErrorLog
    {
        /** @var ErrorLog $log */
        $log = $this->errorRepository->create([
            'exception' => $exception::class,
            'message' => Str::limit($exception->getMessage() ?: $exception::class, 2000, ''),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'user_id' => $user?->id ?? $request?->user()?->id,
            'ip_address' => $request?->ip(),
            'context' => [
                'code' => $exception->getCode(),
            ],
        ]);

        event(new ErrorCaptured($log));

        return $log;
    }
}
