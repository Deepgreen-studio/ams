<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Models\ConsentType;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ConsentTypeRepository extends BaseRepository
{
    public function __construct(ConsentType $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?ConsentType
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ConsentType|null $type */
        $type = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('code', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $type;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): ConsentType
    {
        $type = $this->findByIdentifier($identifier, $withTrashed);

        if (! $type) {
            abort(404, 'Consent type not found.');
        }

        return $type;
    }

    /**
     * Resolve a type for a company: company-specific first, then platform default.
     */
    public function resolveForCompany(string $identifier, int $companyId): ConsentType
    {
        $query = $this->model->newQuery()
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier)
                    ->orWhere('code', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })
            ->where(function (Builder $builder) use ($companyId): void {
                $builder->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            })
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 1 ELSE 0 END');

        /** @var ConsentType|null $type */
        $type = $query->first();

        if (! $type) {
            abort(404, 'Consent type not found.');
        }

        return $type;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 50), 100));

        return $this->filteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ConsentType>
     */
    public function listActive(array $filters = []): Collection
    {
        $filters['is_active'] = true;

        return $this->filteredQuery($filters)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['company_id'])) {
            $companyId = (int) $filters['company_id'];
            $query->where(function (Builder $builder) use ($companyId): void {
                $builder->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        } elseif (($filters['platform_only'] ?? null) === true) {
            $query->whereNull('company_id');
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createType(array $data): ConsentType
    {
        /** @var ConsentType $type */
        $type = $this->model->newQuery()->create($data);

        return $type->fresh() ?? $type;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateType(ConsentType $type, array $data): ConsentType
    {
        $type->fill($data);
        $type->save();

        return $type->refresh();
    }
}
