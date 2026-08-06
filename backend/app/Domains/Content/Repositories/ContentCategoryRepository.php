<?php

namespace App\Domains\Content\Repositories;

use App\Domains\Content\Models\ContentCategory;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ContentCategoryRepository extends BaseRepository
{
    public function __construct(ContentCategory $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?ContentCategory
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ContentCategory|null $category */
        $category = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('slug', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $category;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): ContentCategory
    {
        $category = $this->findByIdentifier($identifier, $withTrashed);

        if (! $category) {
            abort(404, 'Content category not found.');
        }

        return $category;
    }

    /**
     * @return Collection<int, ContentCategory>
     */
    public function listActive(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->with('parent:id,uuid,name,slug')
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
            ->with(['parent:id,uuid,name,slug', 'creator:id,uuid,full_name,email'])
            ->withCount('contents')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ContentCategory>
     */
    public function listFiltered(array $filters = []): Collection
    {
        return $this->filteredQuery($filters)
            ->with(['parent:id,uuid,name,slug', 'children'])
            ->withCount('contents')
            ->get();
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

        if (array_key_exists('parent_id', $filters)) {
            if ($filters['parent_id'] === 'null' || $filters['parent_id'] === '') {
                $query->whereNull('parent_id');
            } elseif (filled($filters['parent_id'])) {
                $parent = $this->findByIdentifier((string) $filters['parent_id']);
                if ($parent) {
                    $query->where('parent_id', $parent->id);
                }
            }
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

    public function isDescendantOf(int $categoryId, int $possibleAncestorId): bool
    {
        $current = $this->model->newQuery()->find($categoryId);
        $guard = 0;

        while ($current && $current->parent_id && $guard < 100) {
            if ((int) $current->parent_id === $possibleAncestorId) {
                return true;
            }
            $current = $this->model->newQuery()->find($current->parent_id);
            $guard++;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): ContentCategory
    {
        /** @var ContentCategory $category */
        $category = $this->model->newQuery()->create($data);
        $category = $category->fresh(['parent:id,uuid,name,slug']) ?? $category;

        return $category->loadCount('contents');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(ContentCategory $category, array $data): ContentCategory
    {
        $category->fill($data);
        $category->save();

        return $category->refresh()->load(['parent:id,uuid,name,slug'])->loadCount('contents');
    }

    /**
     * @param  list<string>  $identifiers
     * @return SupportCollection<int, ContentCategory>
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
     * @param  list<string|int>  $identifiers
     * @return list<int>
     */
    public function resolveIds(array $identifiers): array
    {
        $ids = [];

        foreach ($identifiers as $identifier) {
            if ($identifier === null || $identifier === '') {
                continue;
            }

            $category = $this->findByIdentifier((string) $identifier);
            if ($category) {
                $ids[] = $category->id;
            }
        }

        return array_values(array_unique($ids));
    }
}
