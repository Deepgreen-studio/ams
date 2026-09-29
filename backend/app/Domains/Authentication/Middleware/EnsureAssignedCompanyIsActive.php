<?php

namespace App\Domains\Authentication\Middleware;

use App\Domains\Authentication\Services\AssignedCompanyAccess;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAssignedCompanyIsActive
{
    public function __construct(
        private readonly AssignedCompanyAccess $assignedCompanyAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is(
            'api/v1/auth/login',
            'api/v1/auth/two-factor',
            'api/v1/auth/forgot-password',
            'api/v1/auth/reset-password',
            'api/v1/auth/logout',
            'api/v1/auth/logout-all',
        ) || $request->is('api/v1/auth/verify-email/*')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $message = $this->assignedCompanyAccess->blockMessage($user);

        if ($message === null) {
            return $next($request);
        }

        return ApiResponse::error($message, 403, null, AssignedCompanyAccess::BLOCK_CODE);
    }
}
