<?php

namespace App\Domains\Roles\Repositories;

use App\Domains\Roles\Models\Role;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?Role
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var Role|null $role */
        $role = $query
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier)
                    ->orWhere('name', $identifier);

                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        return $role;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): Role
    {
        $role = $this->findByIdentifier($identifier, $withTrashed);

        if (! $role) {
            abort(404, 'Role not found.');
        }

        return $role;
    }

    public function findByName(string $name, string $guard = 'web'): ?Role
    {
        /** @var Role|null $role */
        $role = $this->model->newQuery()
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->first();

        return $role;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->withCount(['permissions', 'users'])
            ->with('permissions:id,name,display_name,module')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('is_system', $filters) && $filters['is_system'] !== '' && $filters['is_system'] !== null) {
            $query->where('is_system', filter_var($filters['is_system'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'name');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowed = ['id', 'name', 'display_name', 'created_at', 'updated_at', 'is_system'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'name';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRole(array $data): Role
    {
        /** @var Role $role */
        $role = $this->model->newQuery()->create($data);

        return $role->fresh() ?? $role;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRole(Role $role, array $data): Role
    {
        $role->fill($data);
        $role->save();

        return $role->refresh();
    }

    public function softDeleteRole(Role $role): bool
    {
        return (bool) $role->delete();
    }

    public function restoreRole(Role $role): Role
    {
        $role->restore();

        return $role->refresh();
    }

    public function forceDeleteRole(Role $role): bool
    {
        return (bool) $role->forceDelete();
    }

    /**
     * @return array<string, mixed>
     */
    public function activityHistory(Role $role, int $limit = 25): array
    {
        $activities = Activity::query()
            ->forSubject($role)
            ->latest()
            ->limit($limit)
            ->get();

        return [
            'total' => Activity::query()->forSubject($role)->count(),
            'recent' => $activities->map(static function (Activity $activity): array {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'log_name' => $activity->log_name,
                    'created_at' => $activity->created_at,
                    'causer_id' => $activity->causer_id,
                    'properties' => $activity->properties,
                ];
            })->values(),
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    public function allActive(string $guard = 'web'): Collection
    {
        return $this->model->newQuery()
            ->where('guard_name', $guard)
            ->orderBy('display_name')
            ->get();
    }
}
