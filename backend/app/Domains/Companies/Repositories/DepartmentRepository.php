<?php

namespace App\Domains\Companies\Repositories;

use App\Domains\Companies\Models\Department;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DepartmentRepository extends BaseRepository
{
    public function __construct(Department $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?Department
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var Department|null $department */
        $department = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $department;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): Department
    {
        $department = $this->findByIdentifier($identifier, $withTrashed);
        if (! $department) {
            abort(404, 'Department not found.');
        }

        return $department;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $query = $this->model->newQuery()->with([
            'company:id,uuid,company_name',
            'teams' => fn ($teams) => $teams
                ->select(['id', 'uuid', 'department_id', 'name', 'status'])
                ->orderBy('name'),
        ]);

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('company', function (Builder $company) use ($search): void {
                        $company->where('company_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('teams', function (Builder $team) use ($search): void {
                        $team->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'name');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortable = [
            'name' => 'departments.name',
            'created_at' => 'departments.created_at',
        ];

        if ($sortBy === 'company') {
            $query->leftJoin('companies', 'companies.id', '=', 'departments.company_id')
                ->select('departments.*')
                ->orderBy('companies.company_name', $sortDir);
        } else {
            $query->orderBy($sortable[$sortBy] ?? 'departments.name', $sortDir);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
