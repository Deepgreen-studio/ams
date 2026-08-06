<?php

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Requests\IndexUserRequest;
use App\Domains\Users\Requests\StoreUserRequest;
use App\Domains\Users\Requests\UpdateUserRequest;
use App\Domains\Users\Repositories\UserRepository;
use App\Domains\Users\Resources\UserCollection;
use App\Domains\Users\Resources\UserResource;
use App\Domains\Users\Services\UserService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository
    ) {}

    public function index(IndexUserRequest $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $result = $this->userService->list($request->filters());

        return ApiResponse::success([
            'users' => (new UserCollection($result['users']))->resolve(),
            'statistics' => $result['statistics'],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        /** @var User $actor */
        $actor = $request->user();
        $user = $this->userService->create($request->validated(), $actor);

        return ApiResponse::success([
            'user' => new UserResource($user),
        ], 'User created successfully.', 201);
    }

    public function show(string $user): JsonResponse
    {
        $result = $this->userService->show($user);
        $this->authorize('view', $result['user']);

        return ApiResponse::success([
            'user' => new UserResource($result['user']),
            'activity_summary' => $result['activity_summary'],
        ]);
    }

    public function update(UpdateUserRequest $request, string $user): JsonResponse
    {
        $existing = $this->userRepository->findByIdentifierOrFail($user);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->userService->update($user, $request->validated(), $actor);

        return ApiResponse::success([
            'user' => new UserResource($updated),
        ], 'User updated successfully.');
    }

    public function destroy(Request $request, string $user): JsonResponse
    {
        $existing = $this->userRepository->findByIdentifierOrFail($user);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->userService->delete($user, $actor);

        return ApiResponse::success(null, 'User deleted successfully.');
    }

    public function restore(Request $request, string $user): JsonResponse
    {
        $existing = $this->userRepository->findByIdentifierOrFail($user, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->userService->restore($user, $actor);

        return ApiResponse::success([
            'user' => new UserResource($restored),
        ], 'User restored successfully.');
    }

    public function forceDelete(Request $request, string $user): JsonResponse
    {
        $existing = $this->userRepository->findByIdentifierOrFail($user, withTrashed: true);
        $this->authorize('forceDelete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->userService->forceDelete($user, $actor);

        return ApiResponse::success(null, 'User permanently deleted.');
    }
}
