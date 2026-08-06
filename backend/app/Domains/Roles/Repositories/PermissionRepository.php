<?php

namespace App\Domains\Roles\Repositories;

use App\Domains\Roles\Models\Permission;
use App\Domains\Roles\Models\PermissionGroup;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name, string $guard = 'web'): ?Permission
    {
        /** @var Permission|null $permission */
        $permission = $this->model->newQuery()
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->first();

        return $permission;
    }

    /**
     * @param  list<string|int>  $identifiers
     * @return Collection<int, Permission>
     */
    public function findManyByIdentifiers(array $identifiers, string $guard = 'web'): Collection
    {
        $ids = array_values(array_filter($identifiers, static fn ($value) => ctype_digit((string) $value)));
        $names = array_values(array_filter($identifiers, static fn ($value) => ! ctype_digit((string) $value)));

        return $this->model->newQuery()
            ->where('guard_name', $guard)
            ->where(function (Builder $query) use ($ids, $names): void {
                if ($ids !== []) {
                    $query->orWhereIn('id', $ids);
                }

                if ($names !== []) {
                    $query->orWhereIn('name', $names);
                }
            })
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 50), 200));

        return $this->filteredQuery($filters)
            ->with('group:id,uuid,name,slug,module')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['group_id'])) {
            $query->where('permission_group_id', $filters['group_id']);
        }

        return $query->orderBy('module')->orderBy('name');
    }

    /**
     * @return Collection<int, PermissionGroup>
     */
    public function groupsWithPermissions(): Collection
    {
        return PermissionGroup::query()
            ->where('is_active', true)
            ->with(['permissions' => fn ($query) => $query->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function permissionMatrix(?int $roleId = null): array
    {
        $groups = $this->groupsWithPermissions();
        $assigned = collect();

        if ($roleId) {
            $assigned = $this->model->newQuery()
                ->whereHas('roles', fn (Builder $query) => $query->where('roles.id', $roleId))
                ->pluck('id');
        }

        return $groups->map(function (PermissionGroup $group) use ($assigned): array {
            return [
                'id' => $group->id,
                'uuid' => $group->uuid,
                'name' => $group->name,
                'slug' => $group->slug,
                'module' => $group->module,
                'description' => $group->description,
                'permissions' => $group->permissions->map(static function (Permission $permission) use ($assigned): array {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'module' => $permission->module,
                        'description' => $permission->description,
                        'assigned' => $assigned->contains($permission->id),
                    ];
                })->values(),
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPermission(array $data): Permission
    {
        /** @var Permission $permission */
        $permission = $this->model->newQuery()->create($data);

        return $permission->fresh(['group']) ?? $permission;
    }
}
