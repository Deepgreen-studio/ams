<?php

namespace App\Domains\Roles\Controllers;

use App\Domains\Roles\Models\Role;
use App\Domains\Roles\Requests\AssignRoleRequest;
use App\Domains\Roles\Services\RoleService;
use App\Domains\Users\Resources\UserResource;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserRoleController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly RoleService $roleService
    ) {}

    public function store(AssignRoleRequest $request, string $user): JsonResponse
    {
        $this->authorize('assignToUser', Role::class);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->roleService->assignRolesToUser(
            $user,
            $request->validated('roles'),
            $actor
        );

        return ApiResponse::success([
            'user' => new UserResource($updated),
            'roles' => $updated->getRoleNames()->values(),
        ], 'User roles updated successfully.');
    }

    public function destroy(Request $request, string $user, string $role): JsonResponse
    {
        $this->authorize('assignToUser', Role::class);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->roleService->removeRoleFromUser($user, $role, $actor);

        return ApiResponse::success([
            'user' => new UserResource($updated),
            'roles' => $updated->getRoleNames()->values(),
        ], 'Role removed from user successfully.');
    }
}
