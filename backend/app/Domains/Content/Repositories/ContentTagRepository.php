<?php

namespace App\Domains\Content\Repositories;

use App\Domains\Content\Models\ContentTag;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ContentTagRepository extends BaseRepository
{
    public function __construct(ContentTag $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?ContentTag
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ContentTag|null $tag */
        $tag = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('slug', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $tag;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): ContentTag
    {
        $tag = $this->findByIdentifier($identifier, $withTrashed);

        if (! $tag) {
            abort(404, 'Content tag not found.');
        }

        return $tag;
    }

    /**
     * @return Collection<int, ContentTag>
     */
    public function listAll(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with(['creator:id,uuid,full_name,email'])
            ->withCount('contents')
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

        if (($filters['status'] ?? null) === 'active') {
            $query->where('is_active', true);
        } elseif (($filters['status'] ?? null) === 'inactive') {
            $query->where('is_active', false);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('seo_title', 'like', "%{$search}%");
            });
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'sort_order');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowed = ['id', 'name', 'slug', 'sort_order', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'sort_order';
        }

        return $query->orderBy($sortBy, $sortDir)->orderBy('name');
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('slug', $slug)
            ->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTag(array $data): ContentTag
    {
        /** @var ContentTag $tag */
        $tag = $this->model->newQuery()->create($data);

        return $tag->refresh()->loadCount('contents');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTag(ContentTag $tag, array $data): ContentTag
    {
        $tag->fill($data);
        $tag->save();

        return $tag->refresh()->loadCount('contents');
    }

    /**
     * @param  list<string|int>  $identifiers
     * @return list<int>
     */
    public function resolveIds(array $identifiers, ?int $actorId = null): array
    {
        $ids = [];

        foreach ($identifiers as $identifier) {
            if ($identifier === null || $identifier === '') {
                continue;
            }

            $value = is_string($identifier) ? trim($identifier) : $identifier;

            if (is_numeric($value)) {
                $tag = $this->model->newQuery()->find((int) $value);
                if ($tag) {
                    $ids[] = $tag->id;
                }

                continue;
            }

            $tag = $this->findByIdentifier((string) $value);
            if ($tag) {
                $ids[] = $tag->id;

                continue;
            }

            $name = (string) $value;
            $slug = str($name)->slug()->toString() ?: 'tag';
            $base = $slug;
            $suffix = 2;
            while ($this->slugExists($slug)) {
                $slug = $base.'-'.$suffix;
                $suffix++;
            }

            $created = $this->createTag([
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
                'sort_order' => 0,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $ids[] = $created->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<string>  $identifiers
     * @return SupportCollection<int, ContentTag>
     */
    public function findManyByIdentifiers(array $identifiers, bool $withTrashed = false): SupportCollection
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->where(function (Builder $builder) use ($identifiers): void {
            foreach ($identifiers as $identifier) {
                $builder->orWhere(function (Builder $inner) use ($identifier): void {
                    $inner->where('uuid', $identifier)->orWhere('slug', $identifier);
                    if (ctype_digit((string) $identifier)) {
                        $inner->orWhere('id', (int) $identifier);
                    }
                });
            }
        })->get();
    }

    /**
     * @param  list<int>  $ids
     * @return SupportCollection<int, ContentTag>
     */
    public function findManyByIds(array $ids): SupportCollection
    {
        return $this->model->newQuery()->whereIn('id', $ids)->get();
    }
}
