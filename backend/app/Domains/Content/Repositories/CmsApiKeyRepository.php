<?php

namespace App\Domains\Content\Repositories;

use App\Domains\Content\Models\CmsApiKey;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CmsApiKeyRepository extends BaseRepository
{
    public function __construct(CmsApiKey $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?CmsApiKey
    {
        /** @var CmsApiKey|null $key */
        $key = $this->model->newQuery()
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        return $key;
    }

    public function findByIdentifierOrFail(string $identifier): CmsApiKey
    {
        $key = $this->findByIdentifier($identifier);

        if (! $key) {
            abort(404, 'CMS API key not found.');
        }

        return $key;
    }

    public function findByHash(string $hash): ?CmsApiKey
    {
        /** @var CmsApiKey|null $key */
        $key = $this->model->newQuery()
            ->where('key_hash', $hash)
            ->where('is_active', true)
            ->first();

        return $key;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $query = $this->model->newQuery()->with('creator:id,uuid,full_name,email');

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('key_prefix', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createKey(array $data): CmsApiKey
    {
        /** @var CmsApiKey $key */
        $key = $this->model->newQuery()->create($data);

        return $key;
    }
}
