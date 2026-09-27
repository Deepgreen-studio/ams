<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Authentication\Services\TwoFactorService;
use App\Domains\Users\Requests\ConfirmTwoFactorRequest;
use App\Domains\Users\Requests\DisableTwoFactorRequest;
use App\Domains\Users\Requests\UpdateProfileRequest;
use App\Domains\Users\Requests\UploadAvatarRequest;
use App\Domains\Users\Resources\UserResource;
use App\Domains\Users\Services\UserService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly UserService $userService,
        private readonly TwoFactorService $twoFactorService
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('updateProfile', $user);

        $profile = $this->userService->profile($user);

        return ApiResponse::success([
            'user' => new UserResource($profile),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('updateProfile', $user);

        $updated = $this->userService->updateProfile($user, $request->validated());

        return ApiResponse::success([
            'user' => new UserResource($updated),
        ], 'Profile updated successfully.');
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('uploadAvatar', $user);

        $updated = $this->userService->uploadAvatar(
            $user,
            $request->file('avatar'),
            $user
        );

        return ApiResponse::success([
            'user' => new UserResource($updated),
        ], 'Avatar updated successfully.');
    }

    public function beginTwoFactor(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('updateProfile', $user);

        return ApiResponse::success(
            $this->twoFactorService->beginEnrollment($user),
            'Scan the setup key, then confirm with a code from your authenticator app.'
        );
    }

    public function confirmTwoFactor(ConfirmTwoFactorRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('updateProfile', $user);

        $result = $this->twoFactorService->confirmEnrollment($user, $request->validated('code'));

        return ApiResponse::success([
            'user' => new UserResource($user->fresh()),
            'recovery_codes' => $result['recovery_codes'],
        ], 'Multi-factor authentication is enabled.');
    }

    public function disableTwoFactor(DisableTwoFactorRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('updateProfile', $user);

        $this->twoFactorService->disable($user, $request->validated('password'));

        return ApiResponse::success([
            'user' => new UserResource($user->fresh()),
        ], 'Multi-factor authentication is disabled.');
    }
}
