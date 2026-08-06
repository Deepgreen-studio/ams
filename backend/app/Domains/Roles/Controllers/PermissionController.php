<?php

namespace App\Domains\Roles\Controllers;

use App\Domains\Roles\Models\Permission;
use App\Domains\Roles\Models\Role;
use App\Domains\Roles\Resources\PermissionGroupResource;
use App\Domains\Roles\Resources\PermissionResource;
use App\Domains\Roles\Services\PermissionService;
use App\Shared\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PermissionService $permissionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = $this->permissionService->list($request->only([
            'search',
            'module',
            'group_id',
            'per_page',
            'page',
        ]));

        return ApiResponse::success([
            'permissions' => [
                'items' => PermissionResource::collection($permissions->items()),
                'meta' => [
                    'current_page' => $permissions->currentPage(),
                    'last_page' => $permissions->lastPage(),
                    'per_page' => $permissions->perPage(),
                    'total' => $permissions->total(),
                ],
            ],
        ]);
    }

    public function groups(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $groups = $this->permissionService->groups();

        return ApiResponse::success([
            'groups' => PermissionGroupResource::collection($groups),
        ]);
    }

    public function matrix(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $matrix = $this->permissionService->matrix($request->query('role'));

        return ApiResponse::success([
            'matrix' => $matrix,
        ]);
    }
}
