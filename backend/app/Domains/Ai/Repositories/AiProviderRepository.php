<?php

namespace App\Domains\Ai\Repositories;

use App\Domains\Ai\Models\AiProvider;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AiProviderRepository extends BaseRepository
{
    public function __construct(AiProvider $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?AiProvider
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var AiProvider|null $provider */
        $provider = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $provider;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): AiProvider
    {
        $provider = $this->findByIdentifier($identifier, $withTrashed);
        if (! $provider) {
            abort(404, 'AI provider not found.');
        }

        return $provider;
    }

    public function findDefault(?int $companyId = null): ?AiProvider
    {
        $query = $this->model->newQuery()
            ->where('is_enabled', true)
            ->where('is_default', true)
            ->orderByDesc('id');

        if ($companyId !== null) {
            $query->where(function (Builder $builder) use ($companyId): void {
                $builder->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }

        /** @var AiProvider|null $provider */
        $provider = $query->first();

        if ($provider) {
            return $provider;
        }

        return $this->model->newQuery()
            ->where('is_enabled', true)
            ->when($companyId !== null, function (Builder $builder) use ($companyId): void {
                $builder->where(function (Builder $inner) use ($companyId): void {
                    $inner->where('company_id', $companyId)->orWhereNull('company_id');
                });
            })
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->with(['company:id,uuid,company_name', 'creator:id,uuid,full_name,email'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, AiProvider>
     */
    public function enabled(?int $companyId = null): Collection
    {
        return $this->model->newQuery()
            ->where('is_enabled', true)
            ->when($companyId !== null, function (Builder $builder) use ($companyId): void {
                $builder->where(function (Builder $inner) use ($companyId): void {
                    $inner->where('company_id', $companyId)->orWhereNull('company_id');
                });
            })
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'enabled' => $this->model->newQuery()->where('is_enabled', true)->count(),
            'disabled' => $this->model->newQuery()->where('is_enabled', false)->count(),
            'healthy' => $this->model->newQuery()->where('health_status', 'healthy')->count(),
            'defaults' => $this->model->newQuery()->where('is_default', true)->count(),
        ];
    }

    public function clearDefaults(?int $companyId = null, ?int $exceptId = null): void
    {
        $query = $this->model->newQuery()->where('is_default', true);
        if ($companyId === null) {
            $query->whereNull('company_id');
        } else {
            $query->where('company_id', $companyId);
        }
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        $query->update(['is_default' => false]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->latest('id');

        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! blank($filters['driver'] ?? null)) {
            $query->where('driver', $filters['driver']);
        }
        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (array_key_exists('is_enabled', $filters) && $filters['is_enabled'] !== null && $filters['is_enabled'] !== '') {
            $query->where('is_enabled', filter_var($filters['is_enabled'], FILTER_VALIDATE_BOOLEAN));
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('default_model', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
