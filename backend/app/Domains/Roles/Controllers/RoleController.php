<?php

namespace App\Domains\Roles\Controllers;

use App\Domains\Roles\Enums\RolePermission;
use App\Domains\Roles\Models\Role;
use App\Domains\Roles\Requests\AssignPermissionRequest;
use App\Domains\Roles\Requests\IndexRoleRequest;
use App\Domains\Roles\Requests\StoreRoleRequest;
use App\Domains\Roles\Requests\UpdateRoleRequest;
use App\Domains\Roles\Repositories\RoleRepository;
use App\Domains\Roles\Resources\RoleCollection;
use App\Domains\Roles\Resources\RoleResource;
use App\Domains\Roles\Services\RoleService;
use App\Models\User;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly RoleService $roleService,
        private readonly RoleRepository $roleRepository
    ) {}

    public function index(IndexRoleRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = $this->roleService->list($request->filters());

        return ApiResponse::success([
            'roles' => (new RoleCollection($roles))->resolve(),
        ]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        /** @var User $actor */
        $actor = $request->user();
        $role = $this->roleService->create($request->validated(), $actor);

        return ApiResponse::success([
            'role' => new RoleResource($role),
        ], 'Role created successfully.', 201);
    }

    public function show(string $role): JsonResponse
    {
        $result = $this->roleService->show($role);
        $this->authorize('view', $result['role']);

        return ApiResponse::success([
            'role' => new RoleResource($result['role']),
            'activity_history' => $result['activity_history'],
        ]);
    }

    public function update(UpdateRoleRequest $request, string $role): JsonResponse
    {
        $existing = $this->roleRepository->findByIdentifierOrFail($role);
        $this->authorize('update', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->roleService->update($role, $request->validated(), $actor);

        return ApiResponse::success([
            'role' => new RoleResource($updated),
        ], 'Role updated successfully.');
    }

    public function destroy(Request $request, string $role): JsonResponse
    {
        $existing = $this->roleRepository->findByIdentifierOrFail($role);
        $this->authorize('delete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->roleService->delete($role, $actor);

        return ApiResponse::success(null, 'Role deleted successfully.');
    }

    public function restore(Request $request, string $role): JsonResponse
    {
        $existing = $this->roleRepository->findByIdentifierOrFail($role, withTrashed: true);
        $this->authorize('restore', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $restored = $this->roleService->restore($role, $actor);

        return ApiResponse::success([
            'role' => new RoleResource($restored),
        ], 'Role restored successfully.');
    }

    public function forceDelete(Request $request, string $role): JsonResponse
    {
        $existing = $this->roleRepository->findByIdentifierOrFail($role, withTrashed: true);
        $this->authorize('forceDelete', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $this->roleService->forceDelete($role, $actor);

        return ApiResponse::success(null, 'Role permanently deleted.');
    }

    public function syncPermissions(AssignPermissionRequest $request, string $role): JsonResponse
    {
        $existing = $this->roleRepository->findByIdentifierOrFail($role);
        $this->authorize('assignPermissions', $existing);

        /** @var User $actor */
        $actor = $request->user();
        $updated = $this->roleService->syncPermissions(
            $role,
            $request->validated('permissions'),
            $actor
        );

        return ApiResponse::success([
            'role' => new RoleResource($updated),
        ], 'Permissions synced successfully.');
    }
}
