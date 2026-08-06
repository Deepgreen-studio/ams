<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Models\ContentCategory;
use App\Domains\Content\Models\ContentTag;
use App\Domains\Content\Models\ContentType;
use App\Domains\Content\Repositories\ContentCategoryRepository;
use App\Domains\Content\Repositories\ContentStatusRepository;
use App\Domains\Content\Repositories\ContentTagRepository;
use App\Domains\Content\Repositories\ContentTypeRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentCatalogService
{
    public function __construct(
        private readonly ContentTypeRepository $contentTypeRepository,
        private readonly ContentStatusRepository $contentStatusRepository,
        private readonly ContentCategoryRepository $contentCategoryRepository,
        private readonly ContentTagRepository $contentTagRepository
    ) {}

    public function listTypes(): Collection
    {
        return $this->contentTypeRepository->listActive();
    }

    public function listStatuses(): Collection
    {
        return $this->contentStatusRepository->listAll();
    }

    public function listCategories(): Collection
    {
        return $this->contentCategoryRepository->listActive();
    }

    public function listTags(): Collection
    {
        return $this->contentTagRepository->listAll();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createType(array $data, User $actor): ContentType
    {
        return DB::transaction(function () use ($data, $actor): ContentType {
            $name = (string) $data['name'];
            $slug = $this->resolveUniqueTypeSlug($data['slug'] ?? null, $name);

            return $this->contentTypeRepository->createType([
                'name' => $name,
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'is_system' => false,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'sort_order' => (int) ($data['sort_order'] ?? 100),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateType(string $identifier, array $data, User $actor): ContentType
    {
        return DB::transaction(function () use ($identifier, $data, $actor): ContentType {
            $type = $this->contentTypeRepository->findByIdentifierOrFail($identifier);

            if ($type->is_system && array_key_exists('slug', $data) && filled($data['slug']) && $data['slug'] !== $type->slug) {
                throw new ApiException('System content type slug cannot be changed.', 422);
            }

            $payload = [
                'updated_by' => $actor->id,
            ];

            if (array_key_exists('name', $data)) {
                $payload['name'] = $data['name'];
            }

            if (array_key_exists('description', $data)) {
                $payload['description'] = blank($data['description']) ? null : $data['description'];
            }

            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] = (bool) $data['is_active'];
            }

            if (array_key_exists('sort_order', $data)) {
                $payload['sort_order'] = (int) $data['sort_order'];
            }

            if (array_key_exists('slug', $data) && filled($data['slug'])) {
                $payload['slug'] = $this->resolveUniqueTypeSlug((string) $data['slug'], $type->name, $type->id);
            }

            return $this->contentTypeRepository->updateType($type, $payload);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data, User $actor): ContentCategory
    {
        return DB::transaction(function () use ($data, $actor): ContentCategory {
            $name = (string) $data['name'];
            $parentId = null;
            if (! empty($data['parent_id'])) {
                $parentId = $this->contentCategoryRepository->findByIdentifierOrFail((string) $data['parent_id'])->id;
            }

            return $this->contentCategoryRepository->createCategory([
                'parent_id' => $parentId,
                'name' => $name,
                'slug' => $this->resolveUniqueCategorySlug($data['slug'] ?? null, $name),
                'description' => $data['description'] ?? null,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(string $identifier, array $data, User $actor): ContentCategory
    {
        return DB::transaction(function () use ($identifier, $data, $actor): ContentCategory {
            $category = $this->contentCategoryRepository->findByIdentifierOrFail($identifier);
            $payload = ['updated_by' => $actor->id];

            if (array_key_exists('name', $data)) {
                $payload['name'] = $data['name'];
            }

            if (array_key_exists('description', $data)) {
                $payload['description'] = blank($data['description']) ? null : $data['description'];
            }

            if (array_key_exists('is_active', $data)) {
                $payload['is_active'] = (bool) $data['is_active'];
            }

            if (array_key_exists('sort_order', $data)) {
                $payload['sort_order'] = (int) $data['sort_order'];
            }

            if (array_key_exists('parent_id', $data)) {
                if (blank($data['parent_id'])) {
                    $payload['parent_id'] = null;
                } else {
                    $parent = $this->contentCategoryRepository->findByIdentifierOrFail((string) $data['parent_id']);
                    if ($parent->id === $category->id) {
                        throw new ApiException('Category cannot be its own parent.', 422);
                    }
                    $payload['parent_id'] = $parent->id;
                }
            }

            if (array_key_exists('slug', $data) && filled($data['slug'])) {
                $payload['slug'] = $this->resolveUniqueCategorySlug((string) $data['slug'], $category->name, $category->id);
            } elseif (array_key_exists('name', $payload)) {
                $payload['slug'] = $this->resolveUniqueCategorySlug(null, (string) $payload['name'], $category->id);
            }

            return $this->contentCategoryRepository->updateCategory($category, $payload);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTag(array $data, User $actor): ContentTag
    {
        return DB::transaction(function () use ($data, $actor): ContentTag {
            $name = (string) $data['name'];

            return $this->contentTagRepository->createTag([
                'name' => $name,
                'slug' => $this->resolveUniqueTagSlug($data['slug'] ?? null, $name),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }

    protected function resolveUniqueTypeSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'content-type';
        $candidate = $base;
        $suffix = 2;

        while ($this->contentTypeRepository->slugExists($candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function resolveUniqueCategorySlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'category';
        $candidate = $base;
        $suffix = 2;

        while ($this->contentCategoryRepository->slugExists($candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function resolveUniqueTagSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'tag';
        $candidate = $base;
        $suffix = 2;

        while ($this->contentTagRepository->slugExists($candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
