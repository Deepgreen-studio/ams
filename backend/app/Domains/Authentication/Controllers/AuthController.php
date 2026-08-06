<?php

namespace App\Domains\Authentication\Controllers;

use App\Domains\Authentication\Repositories\AuthenticationRepository;
use App\Domains\Authentication\Requests\ChangePasswordRequest;
use App\Domains\Authentication\Requests\ForgotPasswordRequest;
use App\Domains\Authentication\Requests\LoginRequest;
use App\Domains\Authentication\Requests\ResetPasswordRequest;
use App\Domains\Authentication\Resources\AuthenticatedUserResource;
use App\Domains\Authentication\Services\AuthenticationService;
use App\Shared\Exceptions\ApiException;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly AuthenticationRepository $authenticationRepository
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authenticationService->login(
            $request->validated('email'),
            $request->validated('password'),
            (bool) $request->boolean('remember'),
            $request
        );

        return ApiResponse::success([
            'user' => new AuthenticatedUserResource($result['user']),
            'token' => $result['token'],
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authenticationService->logout($request);

        return ApiResponse::success(null, 'Logout successful.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authenticationService->logoutAllDevices($request);

        return ApiResponse::success(null, 'Logged out from all devices.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->authenticationService->currentUser($request);

        return ApiResponse::success([
            'user' => new AuthenticatedUserResource($user),
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authenticationService->refreshSession($request);

        return ApiResponse::success([
            'user' => new AuthenticatedUserResource($result['user']),
            'token' => $result['token'],
        ], 'Session refreshed.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authenticationService->forgotPassword($request->validated('email'));

        return ApiResponse::success(
            null,
            'If the account exists, a password reset link has been sent.'
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authenticationService->resetPassword($request->validated());

        return ApiResponse::success(null, 'Password has been reset successfully.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $this->authenticationService->changePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password')
        );

        return ApiResponse::success([
            'user' => new AuthenticatedUserResource($user),
        ], 'Password changed successfully.');
    }

    public function sendVerificationEmail(Request $request): JsonResponse
    {
        $this->authenticationService->sendEmailVerification($request->user());

        return ApiResponse::success(null, 'Verification link sent.');
    }

    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return ApiResponse::error('Invalid or expired verification link.', 403);
        }

        $user = $this->authenticationRepository->findById($id);

        if (! $user) {
            throw new ApiException('Invalid email verification link.', 403);
        }

        $user = $this->authenticationService->verifyEmail($user, $hash);

        return ApiResponse::success([
            'user' => new AuthenticatedUserResource($user),
        ], 'Email verified successfully.');
    }
}
