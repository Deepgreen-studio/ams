<?php

namespace App\Domains\Companies\Repositories;

use App\Domains\Companies\Models\CompanyLocation;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LocationRepository extends BaseRepository
{
    public function __construct(CompanyLocation $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?CompanyLocation
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var CompanyLocation|null $location */
        $location = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $location;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): CompanyLocation
    {
        $location = $this->findByIdentifier($identifier, $withTrashed);
        if (! $location) {
            abort(404, 'Location not found.');
        }

        return $location;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $query = $this->model->newQuery()->with(['company:id,uuid,company_name']);

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('branch_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('is_headquarters')->orderBy('branch_name')->paginate($perPage)->withQueryString();
    }
}
