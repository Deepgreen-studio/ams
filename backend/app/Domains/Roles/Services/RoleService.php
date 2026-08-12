<?php

namespace App\Domains\Roles\Services;

use App\Domains\Roles\Events\PermissionAssigned;
use App\Domains\Roles\Events\PermissionRemoved;
use App\Domains\Roles\Events\RoleCreated;
use App\Domains\Roles\Events\RoleDeleted;
use App\Domains\Roles\Events\RoleUpdated;
use App\Domains\Roles\Events\UserRoleAssigned;
use App\Domains\Roles\Events\UserRoleRemoved;
use App\Domains\Roles\Models\Role;
use App\Domains\Roles\Repositories\PermissionRepository;
use App\Domains\Roles\Repositories\RoleRepository;
use App\Domains\Users\Repositories\UserRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly UserRepository $userRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->roleRepository->paginateFiltered($filters);
    }

    /**
     * @return array{role: Role, activity_history: array<string, mixed>}
     */
    public function show(string $identifier): array
    {
        $role = $this->roleRepository->findByIdentifierOrFail($identifier);
        $role->load(['permissions']);
        $role->loadCount(['permissions', 'users']);

        return [
            'role' => $role,
            'activity_history' => $this->roleRepository->activityHistory($role),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Role
    {
        return DB::transaction(function () use ($data, $actor): Role {
            $payload = [
                'name' => Str::slug((string) $data['name'], '-'),
                'display_name' => $data['display_name'] ?? $data['name'],
                'description' => $data['description'] ?? null,
                'guard_name' => $data['guard_name'] ?? 'web',
                'is_system' => (bool) ($data['is_system'] ?? false),
            ];

            if ($this->roleRepository->findByName($payload['name'], $payload['guard_name'])) {
                throw new ApiException('A role with this name already exists.', 422);
            }

            $role = $this->roleRepository->createRole($payload);

            if (! empty($data['permissions']) && is_array($data['permissions'])) {
                $this->syncRolePermissions($role, $data['permissions'], $actor, fireEvents: false);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            event(new RoleCreated($role->fresh(['permissions']) ?? $role, $actor));

            return $role->fresh(['permissions']) ?? $role;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): Role
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Role {
            $role = $this->roleRepository->findByIdentifierOrFail($identifier);

            if ($role->is_system && array_key_exists('name', $data) && $data['name'] !== $role->name) {
                throw new ApiException('System role machine names cannot be renamed.', 422);
            }

            $payload = array_filter([
                'display_name' => $data['display_name'] ?? null,
                'description' => array_key_exists('description', $data) ? $data['description'] : null,
            ], static fn ($value) => $value !== null);

            if (array_key_exists('description', $data) && $data['description'] === null) {
                $payload['description'] = null;
            }

            if (! empty($data['name']) && ! $role->is_system) {
                $newName = Str::slug((string) $data['name'], '-');
                $existing = $this->roleRepository->findByName($newName, $role->guard_name);
                if ($existing && $existing->id !== $role->id) {
                    throw new ApiException('A role with this name already exists.', 422);
                }
                $payload['name'] = $newName;
            }

            $role = $this->roleRepository->updateRole($role, $payload);

            if (array_key_exists('permissions', $data) && is_array($data['permissions'])) {
                $this->syncRolePermissions($role, $data['permissions'], $actor, fireEvents: false);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            event(new RoleUpdated($role->fresh(['permissions']) ?? $role, $actor));

            return $role->fresh(['permissions']) ?? $role;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $role = $this->roleRepository->findByIdentifierOrFail($identifier);

            if ($role->is_system) {
                throw new ApiException('System roles cannot be deleted.', 422);
            }

            if ($role->users()->exists()) {
                throw new ApiException('Cannot delete a role that is still assigned to users.', 422);
            }

            // Free Spatie's unique name+guard index while retaining recoverability.
            $originalName = $role->name;
            $this->roleRepository->updateRole($role, [
                'name' => $originalName.'__deleted_'.$role->id,
                'display_name' => $role->display_name ?: $originalName,
            ]);

            $this->roleRepository->softDeleteRole($role->refresh());
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            event(new RoleDeleted($role, $actor));
        });
    }

    public function restore(string $identifier, User $actor): Role
    {
        return DB::transaction(function () use ($identifier, $actor): Role {
            $role = $this->roleRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $role->trashed()) {
                throw new ApiException('Role is not deleted.', 422);
            }

            $restoredName = preg_replace('/__deleted_\d+$/', '', (string) $role->name) ?: $role->name;
            $conflict = $this->roleRepository->findByName($restoredName, $role->guard_name);

            if ($conflict && $conflict->id !== $role->id) {
                throw new ApiException('Cannot restore role because another active role uses this name.', 422);
            }

            $role = $this->roleRepository->restoreRole($role);
            $role = $this->roleRepository->updateRole($role, [
                'name' => $restoredName,
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            event(new RoleUpdated($role, $actor));

            return $role->load('permissions');
        });
    }

    public function forceDelete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $role = $this->roleRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if ($role->is_system) {
                throw new ApiException('System roles cannot be permanently deleted.', 422);
            }

            if ($role->users()->exists()) {
                throw new ApiException('Cannot permanently delete a role that is still assigned to users.', 422);
            }

            $this->roleRepository->forceDeleteRole($role);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            event(new RoleDeleted($role, $actor));
        });
    }

    /**
     * @param  list<string|int>  $permissionIdentifiers
     */
    public function syncPermissions(string $roleIdentifier, array $permissionIdentifiers, User $actor): Role
    {
        return DB::transaction(function () use ($roleIdentifier, $permissionIdentifiers, $actor): Role {
            $role = $this->roleRepository->findByIdentifierOrFail($roleIdentifier);
            $this->syncRolePermissions($role, $permissionIdentifiers, $actor);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role->fresh(['permissions']) ?? $role;
        });
    }

    /**
     * @param  list<string|int>  $roleIdentifiers
     */
    public function assignRolesToUser(string $userIdentifier, array $roleIdentifiers, User $actor): User
    {
        return DB::transaction(function () use ($userIdentifier, $roleIdentifiers, $actor): User {
            $user = $this->userRepository->findByIdentifierOrFail($userIdentifier);
            $roles = collect($roleIdentifiers)
                ->map(fn ($id) => $this->roleRepository->findByIdentifierOrFail((string) $id))
                ->all();

            $before = $user->getRoleNames()->all();
            $user->syncRoles($roles);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $after = $user->fresh()?->getRoleNames()->all() ?? [];
            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            foreach ($added as $roleName) {
                event(new UserRoleAssigned($user, $roleName, $actor));
            }

            foreach ($removed as $roleName) {
                event(new UserRoleRemoved($user, $roleName, $actor));
            }

            return $user->load('roles');
        });
    }

    public function removeRoleFromUser(string $userIdentifier, string $roleIdentifier, User $actor): User
    {
        return DB::transaction(function () use ($userIdentifier, $roleIdentifier, $actor): User {
            $user = $this->userRepository->findByIdentifierOrFail($userIdentifier);
            $role = $this->roleRepository->findByIdentifierOrFail($roleIdentifier);

            if (! $user->hasRole($role)) {
                throw new ApiException('User does not have this role.', 422);
            }

            $user->removeRole($role);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            event(new UserRoleRemoved($user, $role->name, $actor));

            return $user->load('roles');
        });
    }

    /**
     * @param  list<string|int>  $permissionIdentifiers
     */
    protected function syncRolePermissions(
        Role $role,
        array $permissionIdentifiers,
        User $actor,
        bool $fireEvents = true
    ): void {
        $permissions = $this->permissionRepository->findManyByIdentifiers(
            $permissionIdentifiers,
            $role->guard_name
        );

        if ($permissions->count() !== count(array_unique($permissionIdentifiers))) {
            throw new ApiException('One or more permissions are invalid.', 422);
        }

        $before = $role->permissions()->pluck('name')->all();
        $role->syncPermissions($permissions);
        $after = $role->permissions()->pluck('name')->all();

        if (! $fireEvents) {
            return;
        }

        foreach (array_values(array_diff($after, $before)) as $name) {
            event(new PermissionAssigned($role, $name, $actor));
        }

        foreach (array_values(array_diff($before, $after)) as $name) {
            event(new PermissionRemoved($role, $name, $actor));
        }
    }
}
