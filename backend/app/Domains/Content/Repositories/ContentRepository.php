<?php

namespace App\Domains\Content\Repositories;

use App\Domains\Content\Models\Content;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ContentRepository extends BaseRepository
{
    public function __construct(Content $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?Content
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var Content|null $content */
        $content = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $content;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): Content
    {
        $content = $this->findByIdentifier($identifier, $withTrashed);

        if (! $content) {
            abort(404, 'Content not found.');
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'type:id,uuid,name,slug',
                'status:id,uuid,name,slug,color',
                'category:id,uuid,name,slug',
                'tags:id,uuid,name,slug',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ])
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

        if (! empty($filters['content_type_id'])) {
            $query->where('content_type_id', (int) $filters['content_type_id']);
        }

        if (! empty($filters['content_status_id'])) {
            $query->where('content_status_id', (int) $filters['content_status_id']);
        }

        if (! empty($filters['content_category_id'])) {
            $query->where('content_category_id', (int) $filters['content_category_id']);
        }

        if (! empty($filters['type'])) {
            $type = (string) $filters['type'];
            $query->whereHas('type', function (Builder $builder) use ($type): void {
                $builder->where('slug', $type)->orWhere('uuid', $type);
                if (ctype_digit($type)) {
                    $builder->orWhere('id', (int) $type);
                }
            });
        }

        if (! empty($filters['status'])) {
            $status = (string) $filters['status'];
            if ($status === '__none__') {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('status', function (Builder $builder) use ($status): void {
                    $builder->where('slug', $status)->orWhere('uuid', $status);
                    if (ctype_digit($status)) {
                        $builder->orWhere('id', (int) $status);
                    }
                });
            }
        }

        if (! empty($filters['statuses']) && is_array($filters['statuses'])) {
            $statuses = array_values(array_filter(array_map('strval', $filters['statuses'])));
            if ($statuses === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('status', function (Builder $builder) use ($statuses): void {
                    $builder->whereIn('slug', $statuses);
                });
            }
        }

        if (! empty($filters['category'])) {
            $category = (string) $filters['category'];
            $query->where(function (Builder $builder) use ($category): void {
                $builder->whereHas('category', function (Builder $inner) use ($category): void {
                    $inner->where('slug', $category)->orWhere('uuid', $category);
                    if (ctype_digit($category)) {
                        $inner->orWhere('id', (int) $category);
                    }
                })->orWhereHas('categories', function (Builder $inner) use ($category): void {
                    $inner->where('slug', $category)->orWhere('uuid', $category);
                    if (ctype_digit($category)) {
                        $inner->orWhere('content_categories.id', (int) $category);
                    }
                });
            });
        }

        if (! empty($filters['tag'])) {
            $tag = (string) $filters['tag'];
            $query->whereHas('tags', function (Builder $builder) use ($tag): void {
                $builder->where('slug', $tag)->orWhere('uuid', $tag);
                if (ctype_digit($tag)) {
                    $builder->orWhere('content_tags.id', (int) $tag);
                }
            });
        }

        if (array_key_exists('is_featured', $filters) && $filters['is_featured'] !== '' && $filters['is_featured'] !== null) {
            $query->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('seo_title', 'like', "%{$search}%");
            });
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = [
            'id',
            'title',
            'slug',
            'published_at',
            'sort_order',
            'is_featured',
            'view_count',
            'created_at',
            'updated_at',
        ];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createContent(array $data): Content
    {
        /** @var Content $content */
        $content = $this->model->newQuery()->create($data);

        return $content->fresh($this->defaultRelations()) ?? $content;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateContent(Content $content, array $data): Content
    {
        $content->fill($data);
        $content->save();

        return $content->refresh()->load($this->defaultRelations());
    }

    /**
     * @param  list<int>  $tagIds
     */
    public function syncTags(Content $content, array $tagIds): Content
    {
        $content->tags()->sync($tagIds);

        return $content->refresh()->load($this->defaultRelations());
    }

    /**
     * @param  list<int>  $categoryIds
     */
    public function syncCategories(Content $content, array $categoryIds): Content
    {
        $content->categories()->sync($categoryIds);

        return $content->refresh()->load($this->defaultRelations());
    }

    public function slugExistsForType(int $typeId, string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('content_type_id', $typeId)
            ->where('slug', $slug)
            ->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        $statusCounts = $this->model->newQuery()
            ->select('content_statuses.slug', DB::raw('COUNT(contents.id) as aggregate'))
            ->join('content_statuses', 'content_statuses.id', '=', 'contents.content_status_id')
            ->whereNull('contents.deleted_at')
            ->groupBy('content_statuses.slug')
            ->pluck('aggregate', 'slug')
            ->map(fn ($value) => (int) $value)
            ->all();

        return [
            'total' => (int) $this->model->newQuery()->count(),
            'draft' => (int) ($statusCounts['draft'] ?? 0),
            'pending_review' => (int) ($statusCounts['pending_review'] ?? 0),
            'reviewed' => (int) ($statusCounts['reviewed'] ?? 0),
            'approved' => (int) ($statusCounts['approved'] ?? 0),
            'rejected' => (int) ($statusCounts['rejected'] ?? 0),
            'published' => (int) ($statusCounts['published'] ?? 0),
            'scheduled' => (int) ($statusCounts['scheduled'] ?? 0),
            'archived' => (int) ($statusCounts['archived'] ?? 0),
            'featured' => (int) $this->model->newQuery()->where('is_featured', true)->count(),
            'trashed' => (int) $this->model->newQuery()->onlyTrashed()->count(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function defaultRelations(): array
    {
        return [
            'type:id,uuid,name,slug',
            'status:id,uuid,name,slug,color',
            'category:id,uuid,name,slug',
            'categories:id,uuid,name,slug',
            'tags:id,uuid,name,slug',
            'publisher:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ];
    }
}
