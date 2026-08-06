<?php

namespace App\Domains\Users\Controllers;

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
        private readonly UserService $userService
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
}
