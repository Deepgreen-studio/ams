<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Models\ContentCategory;
use App\Domains\Content\Repositories\ContentCategoryRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentCategoryService
{
    public function __construct(
        private readonly ContentCategoryRepository $categoryRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->categoryRepository->paginateFiltered($filters);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tree(array $filters = []): array
    {
        $filters['trashed'] = $filters['trashed'] ?? '';
        $categories = $this->categoryRepository->listFiltered($filters);

        return $this->buildTree($categories);
    }

    public function find(string $identifier, bool $withTrashed = false): ContentCategory
    {
        return $this->categoryRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): ContentCategory
    {
        return $this->find($identifier)
            ->load(['parent:id,uuid,name,slug', 'children', 'creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email'])
            ->loadCount('contents');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ContentCategory
    {
        return DB::transaction(function () use ($data, $actor): ContentCategory {
            $name = (string) $data['name'];
            $parentId = $this->resolveParentId($data['parent_id'] ?? null);

            $category = $this->categoryRepository->createCategory([
                'parent_id' => $parentId,
                'name' => $name,
                'slug' => $this->resolveUniqueSlug($data['slug'] ?? null, $name),
                'description' => $data['description'] ?? null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            activity('content')
                ->causedBy($actor)
                ->performedOn($category)
                ->withProperties(['event' => 'content_category_created', 'name' => $category->name])
                ->log('Content category created');

            return $category;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): ContentCategory
    {
        return DB::transaction(function () use ($identifier, $data, $actor): ContentCategory {
            $category = $this->categoryRepository->findByIdentifierOrFail($identifier);
            $payload = ['updated_by' => $actor->id];

            foreach (['name', 'description', 'seo_title', 'seo_description'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = blank($data[$field]) && $field !== 'name' ? null : $data[$field];
                }
            }

            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] = (bool) $data['is_active'];
            }

            if (array_key_exists('sort_order', $data)) {
                $payload['sort_order'] = (int) $data['sort_order'];
            }

            if (array_key_exists('parent_id', $data)) {
                $parentId = $this->resolveParentId($data['parent_id']);
                if ($parentId === $category->id) {
                    throw new ApiException('Category cannot be its own parent.', 422);
                }
                if ($parentId !== null && $this->categoryRepository->isDescendantOf($parentId, $category->id)) {
                    throw new ApiException('Cannot assign a descendant category as parent.', 422);
                }
                $payload['parent_id'] = $parentId;
            }

            if (array_key_exists('slug', $data) && filled($data['slug'])) {
                $payload['slug'] = $this->resolveUniqueSlug((string) $data['slug'], $category->name, $category->id);
            } elseif (array_key_exists('name', $payload)) {
                $payload['slug'] = $this->resolveUniqueSlug(null, (string) $payload['name'], $category->id);
            }

            $updated = $this->categoryRepository->updateCategory($category, $payload);

            activity('content')
                ->causedBy($actor)
                ->performedOn($updated)
                ->withProperties(['event' => 'content_category_updated', 'name' => $updated->name])
                ->log('Content category updated');

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $category = $this->categoryRepository->findByIdentifierOrFail($identifier);

            if ($category->children()->exists()) {
                throw new ApiException('Reassign or delete child categories before deleting this category.', 422);
            }

            $this->categoryRepository->updateCategory($category, ['updated_by' => $actor->id]);
            $category->delete();

            activity('content')
                ->causedBy($actor)
                ->performedOn($category)
                ->withProperties(['event' => 'content_category_deleted', 'name' => $category->name])
                ->log('Content category deleted');
        });
    }

    public function restore(string $identifier, User $actor): ContentCategory
    {
        return DB::transaction(function () use ($identifier, $actor): ContentCategory {
            $category = $this->categoryRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $category->trashed()) {
                throw new ApiException('Category is not deleted.', 422);
            }

            $category->restore();
            $restored = $this->categoryRepository->updateCategory($category, ['updated_by' => $actor->id]);

            activity('content')
                ->causedBy($actor)
                ->performedOn($restored)
                ->withProperties(['event' => 'content_category_restored', 'name' => $restored->name])
                ->log('Content category restored');

            return $restored;
        });
    }

    /**
     * @param  array{action: string, ids: list<string>}  $payload
     * @return array{affected: int}
     */
    public function bulk(array $payload, User $actor): array
    {
        return DB::transaction(function () use ($payload, $actor): array {
            $action = (string) $payload['action'];
            $categories = $this->categoryRepository->findManyByIdentifiers($payload['ids'] ?? [], withTrashed: $action === 'restore');
            $affected = 0;

            foreach ($categories as $category) {
                match ($action) {
                    'activate' => $this->categoryRepository->updateCategory($category, [
                        'is_active' => true,
                        'updated_by' => $actor->id,
                    ]),
                    'deactivate' => $this->categoryRepository->updateCategory($category, [
                        'is_active' => false,
                        'updated_by' => $actor->id,
                    ]),
                    'delete' => $this->safeBulkDelete($category, $actor),
                    'restore' => $category->trashed()
                        ? $this->restore($category->uuid, $actor)
                        : null,
                    default => throw new ApiException('Unsupported bulk action.', 422),
                };
                $affected++;
            }

            activity('content')
                ->causedBy($actor)
                ->withProperties([
                    'event' => 'content_category_bulk',
                    'action' => $action,
                    'affected' => $affected,
                    'ids' => $payload['ids'] ?? [],
                ])
                ->log('Content category bulk '.$action);

            return ['affected' => $affected];
        });
    }

    protected function safeBulkDelete(ContentCategory $category, User $actor): void
    {
        if ($category->children()->exists()) {
            return;
        }

        $this->categoryRepository->updateCategory($category, ['updated_by' => $actor->id]);
        $category->delete();
    }

    protected function resolveParentId(mixed $identifier): ?int
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        return $this->categoryRepository->findByIdentifierOrFail((string) $identifier)->id;
    }

    protected function resolveUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'category';
        $candidate = $base;
        $suffix = 2;

        while ($this->categoryRepository->slugExists($candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     * @return list<array<string, mixed>>
     */
    protected function buildTree(Collection $categories): array
    {
        $grouped = $categories->groupBy(fn (ContentCategory $category) => $category->parent_id ?: 0);

        $build = function ($parentKey) use (&$build, $grouped): array {
            return ($grouped->get($parentKey) ?? collect())
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['name', 'asc'],
                ])
                ->values()
                ->map(function (ContentCategory $category) use (&$build): array {
                    return [
                        'id' => $category->id,
                        'uuid' => $category->uuid,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'seo_title' => $category->seo_title,
                        'seo_description' => $category->seo_description,
                        'is_active' => (bool) $category->is_active,
                        'sort_order' => (int) $category->sort_order,
                        'contents_count' => (int) ($category->contents_count ?? 0),
                        'children' => $build($category->id),
                    ];
                })
                ->all();
        };

        return $build(0);
    }
}
