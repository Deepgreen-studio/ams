<?php

namespace App\Domains\Roles\Services;

use App\Domains\Roles\Models\Permission;
use App\Domains\Roles\Repositories\PermissionRepository;
use App\Domains\Roles\Repositories\RoleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class PermissionService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly RoleRepository $roleRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->permissionRepository->paginateFiltered($filters);
    }

    public function groups(): Collection
    {
        return $this->permissionRepository->groupsWithPermissions();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function matrix(?string $roleIdentifier = null): array
    {
        $roleId = null;

        if ($roleIdentifier) {
            $roleId = $this->roleRepository->findByIdentifierOrFail($roleIdentifier)->id;
        }

        return $this->permissionRepository->permissionMatrix($roleId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Permission
    {
        return DB::transaction(function () use ($data): Permission {
            $name = (string) $data['name'];
            $module = $data['module'] ?? Str::before($name, '.');

            $permission = $this->permissionRepository->createPermission([
                'name' => $name,
                'guard_name' => $data['guard_name'] ?? 'web',
                'display_name' => $data['display_name'] ?? Str::of($name)->replace('.', ' ')->title()->toString(),
                'module' => $module,
                'description' => $data['description'] ?? null,
                'permission_group_id' => $data['permission_group_id'] ?? null,
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $permission;
        });
    }
}
