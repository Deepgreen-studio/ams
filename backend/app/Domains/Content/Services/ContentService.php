<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Enums\ContentBodyFormat;
use App\Domains\Content\Enums\ContentStatusSlug;
use App\Domains\Content\Events\ContentCreated;
use App\Domains\Content\Events\ContentDeleted;
use App\Domains\Content\Events\ContentPublished;
use App\Domains\Content\Events\ContentRestored;
use App\Domains\Content\Events\ContentUnpublished;
use App\Domains\Content\Events\ContentUpdated;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Repositories\ContentCategoryRepository;
use App\Domains\Content\Repositories\ContentRepository;
use App\Domains\Content\Repositories\ContentStatusRepository;
use App\Domains\Content\Repositories\ContentTagRepository;
use App\Domains\Content\Repositories\ContentTypeRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentService
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ContentTypeRepository $contentTypeRepository,
        private readonly ContentStatusRepository $contentStatusRepository,
        private readonly ContentCategoryRepository $contentCategoryRepository,
        private readonly ContentTagRepository $contentTagRepository,
        private readonly ContentVersionService $contentVersionService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->contentRepository->paginateFiltered($filters);
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return $this->contentRepository->statistics();
    }

    public function find(string $identifier, bool $withTrashed = false): Content
    {
        return $this->contentRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): Content
    {
        return $this->find($identifier)->load([
            'type:id,uuid,name,slug,description',
            'status:id,uuid,name,slug,color',
            'category:id,uuid,name,slug',
            'categories:id,uuid,name,slug',
            'tags:id,uuid,name,slug',
            'publisher:id,uuid,full_name,email',
            'submitter:id,uuid,full_name,email',
            'reviewer:id,uuid,full_name,email',
            'approver:id,uuid,full_name,email',
            'rejector:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Content
    {
        return DB::transaction(function () use ($data, $actor): Content {
            $type = $this->contentTypeRepository->findByIdentifierOrFail((string) $data['content_type_id']);
            $statusSlug = (string) ($data['status'] ?? ContentStatusSlug::Draft->value);
            if ($statusSlug === ContentStatusSlug::Published->value) {
                throw new ApiException('New content cannot be published directly. Submit it through the approval workflow.', 422);
            }
            $status = $this->contentStatusRepository->findBySlugOrFail($statusSlug);

            $payload = $this->preparePayload($data);
            $payload['content_type_id'] = $type->id;
            $payload['content_status_id'] = $status->id;
            $categoryIds = $this->resolveCategoryIds($data);
            $payload['content_category_id'] = $categoryIds[0] ?? null;
            $payload['slug'] = $this->resolveUniqueSlug(
                $type->id,
                $payload['slug'] ?? null,
                $payload['title']
            );
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
            $payload['version'] = 1;

            if ($status->slug === ContentStatusSlug::Published->value) {
                $payload['published_at'] = $payload['published_at'] ?? now();
                $payload['published_by'] = $actor->id;
            }

            $content = $this->contentRepository->createContent($payload);

            if ($categoryIds !== []) {
                $content = $this->contentRepository->syncCategories($content, $categoryIds);
            }

            if (array_key_exists('tags', $data)) {
                $tagIds = $this->contentTagRepository->resolveIds(
                    is_array($data['tags']) ? $data['tags'] : [],
                    $actor->id
                );
                $content = $this->contentRepository->syncTags($content, $tagIds);
            }

            $content = $content->load(['status', 'type', 'categories', 'tags']);
            $this->contentVersionService->recordVersion(
                $content,
                (string) ($data['reason'] ?? 'Initial version'),
                $actor
            );

            event(new ContentCreated($content, $actor));

            return $content;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): Content
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Content {
            $content = $this->contentRepository->findByIdentifierOrFail($identifier);
            $this->contentVersionService->ensureBaselineVersion(
                $content->load(['status', 'type', 'categories', 'tags']),
                $actor
            );
            $content = $content->fresh() ?? $content;

            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;
            $payload['version'] = ((int) $content->version) + 1;

            if (array_key_exists('content_type_id', $data) && filled($data['content_type_id'])) {
                $type = $this->contentTypeRepository->findByIdentifierOrFail((string) $data['content_type_id']);
                $payload['content_type_id'] = $type->id;
            }

            if (array_key_exists('status', $data) && filled($data['status'])) {
                $status = $this->contentStatusRepository->findBySlugOrFail((string) $data['status']);
                if ($status->slug === ContentStatusSlug::Published->value) {
                    throw new ApiException('Publishing requires the approval workflow (approved → publish).', 422);
                }
                $payload['content_status_id'] = $status->id;

                if ($status->slug === ContentStatusSlug::Published->value && blank($content->published_at)) {
                    $payload['published_at'] = $data['published_at'] ?? now();
                    $payload['published_by'] = $actor->id;
                }
            }

            if (array_key_exists('categories', $data) || array_key_exists('content_category_id', $data)) {
                $categoryIds = $this->resolveCategoryIds($data);
                $payload['content_category_id'] = $categoryIds[0] ?? null;
            }

            $typeId = $payload['content_type_id'] ?? $content->content_type_id;
            if (array_key_exists('slug', $payload) || array_key_exists('title', $payload) || isset($payload['content_type_id'])) {
                $title = $payload['title'] ?? $content->title;
                $slugInput = $payload['slug'] ?? null;
                $payload['slug'] = $this->resolveUniqueSlug(
                    (int) $typeId,
                    $slugInput,
                    $title,
                    $content->id
                );
            }

            $updated = $this->contentRepository->updateContent($content, $payload);

            if (array_key_exists('categories', $data) || array_key_exists('content_category_id', $data)) {
                $updated = $this->contentRepository->syncCategories($updated, $categoryIds ?? []);
            }

            if (array_key_exists('tags', $data)) {
                $tagIds = $this->contentTagRepository->resolveIds(
                    is_array($data['tags']) ? $data['tags'] : [],
                    $actor->id
                );
                $updated = $this->contentRepository->syncTags($updated, $tagIds);
            }

            $updated = $updated->load(['status', 'type', 'categories', 'tags']);
            $this->contentVersionService->recordVersion(
                $updated,
                (string) ($data['reason'] ?? 'Content updated'),
                $actor
            );

            event(new ContentUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $content = $this->contentRepository->findByIdentifierOrFail($identifier);
            $this->contentRepository->updateContent($content, ['updated_by' => $actor->id]);
            $content->delete();
            event(new ContentDeleted($content, $actor));
        });
    }

    public function restore(string $identifier, User $actor): Content
    {
        return DB::transaction(function () use ($identifier, $actor): Content {
            $content = $this->contentRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $content->trashed()) {
                throw new ApiException('Content is not deleted.', 422);
            }

            $content->restore();
            $restored = $this->contentRepository->updateContent($content, ['updated_by' => $actor->id]);
            event(new ContentRestored($restored, $actor));

            return $restored;
        });
    }

    public function publish(string $identifier, User $actor, ?string $publishedAt = null): Content
    {
        return DB::transaction(function () use ($identifier, $actor, $publishedAt): Content {
            $content = $this->contentRepository->findByIdentifierOrFail($identifier)->load('status');

            if ($content->status?->slug !== ContentStatusSlug::Approved->value) {
                throw new ApiException('Content must be approved before it can be published.', 422);
            }

            $this->contentVersionService->ensureBaselineVersion(
                $content->load(['status', 'type', 'categories', 'tags']),
                $actor
            );
            $content = $content->fresh() ?? $content;
            $status = $this->contentStatusRepository->findBySlugOrFail(ContentStatusSlug::Published->value);

            $updated = $this->contentRepository->updateContent($content, [
                'content_status_id' => $status->id,
                'published_at' => $publishedAt ?: now(),
                'published_by' => $actor->id,
                'updated_by' => $actor->id,
                'version' => ((int) $content->version) + 1,
                'current_workflow_level' => null,
            ]);

            $updated = $updated->load(['status', 'type', 'categories', 'tags']);
            $this->contentVersionService->recordVersion($updated, 'Content published', $actor);

            event(new ContentPublished($updated, $actor));

            return $updated;
        });
    }

    public function unpublish(string $identifier, User $actor): Content
    {
        return DB::transaction(function () use ($identifier, $actor): Content {
            $content = $this->contentRepository->findByIdentifierOrFail($identifier);
            $this->contentVersionService->ensureBaselineVersion(
                $content->load(['status', 'type', 'categories', 'tags']),
                $actor
            );
            $content = $content->fresh() ?? $content;
            $status = $this->contentStatusRepository->findBySlugOrFail(ContentStatusSlug::Draft->value);

            $updated = $this->contentRepository->updateContent($content, [
                'content_status_id' => $status->id,
                'updated_by' => $actor->id,
                'version' => ((int) $content->version) + 1,
            ]);

            $updated = $updated->load(['status', 'type', 'categories', 'tags']);
            $this->contentVersionService->recordVersion($updated, 'Content unpublished', $actor);

            event(new ContentUnpublished($updated, $actor));

            return $updated;
        });
    }

    /**
     * Quiet draft autosave (no domain activity event spam).
     *
     * @param  array<string, mixed>  $data
     */
    public function autosave(string $identifier, array $data, User $actor): Content
    {
        return DB::transaction(function () use ($identifier, $data, $actor): Content {
            $content = $this->contentRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;
            $payload['last_autosaved_at'] = now();

            if (array_key_exists('slug', $payload) || array_key_exists('title', $payload)) {
                $title = $payload['title'] ?? $content->title;
                if (filled($title)) {
                    $payload['slug'] = $this->resolveUniqueSlug(
                        (int) $content->content_type_id,
                        $payload['slug'] ?? null,
                        (string) $title,
                        $content->id
                    );
                } else {
                    unset($payload['slug']);
                }
            }

            return Content::withoutEvents(function () use ($content, $payload): Content {
                return $this->contentRepository->updateContent($content, $payload);
            });
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'title',
            'slug',
            'summary',
            'excerpt',
            'body',
            'body_format',
            'editor_json',
            'featured_image',
            'seo_title',
            'seo_description',
            'seo_keywords',
            'canonical_url',
            'og_title',
            'og_description',
            'og_image',
            'twitter_card',
            'twitter_title',
            'twitter_description',
            'twitter_image',
            'schema_type',
            'schema_json',
            'metadata',
            'is_featured',
            'sort_order',
            'published_at',
            'last_autosaved_at',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach ([
            'summary',
            'excerpt',
            'body',
            'featured_image',
            'seo_title',
            'seo_description',
            'seo_keywords',
            'canonical_url',
            'og_title',
            'og_description',
            'og_image',
            'twitter_card',
            'twitter_title',
            'twitter_description',
            'twitter_image',
            'schema_type',
            'slug',
            'published_at',
        ] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        if (array_key_exists('editor_json', $payload) && ($payload['editor_json'] === '' || $payload['editor_json'] === null)) {
            $payload['editor_json'] = null;
        }

        if (array_key_exists('schema_json', $payload) && ($payload['schema_json'] === '' || $payload['schema_json'] === [])) {
            $payload['schema_json'] = null;
        }

        if (array_key_exists('body_format', $payload)) {
            $format = (string) $payload['body_format'];
            $payload['body_format'] = in_array($format, ContentBodyFormat::values(), true)
                ? $format
                : ContentBodyFormat::Rich->value;
        }

        if (array_key_exists('metadata', $payload) && $payload['metadata'] === null) {
            $payload['metadata'] = null;
        }

        if (array_key_exists('is_featured', $payload)) {
            $payload['is_featured'] = (bool) $payload['is_featured'];
        }

        if (array_key_exists('sort_order', $payload)) {
            $payload['sort_order'] = (int) $payload['sort_order'];
        }

        if ($isUpdate && array_key_exists('slug', $payload) && $payload['slug'] === null) {
            unset($payload['slug']);
        }

        return $payload;
    }

    protected function resolveCategoryId(mixed $identifier): ?int
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        return $this->contentCategoryRepository->findByIdentifierOrFail((string) $identifier)->id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    protected function resolveCategoryIds(array $data): array
    {
        $identifiers = [];

        if (array_key_exists('categories', $data) && is_array($data['categories'])) {
            $identifiers = $data['categories'];
        } elseif (array_key_exists('content_category_id', $data) && filled($data['content_category_id'])) {
            $identifiers = [$data['content_category_id']];
        }

        return $this->contentCategoryRepository->resolveIds($identifiers);
    }

    protected function resolveUniqueSlug(int $typeId, ?string $slug, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $title);
        if ($base === '') {
            $base = 'content';
        }

        $candidate = $base;
        $suffix = 2;

        while ($this->contentRepository->slugExistsForType($typeId, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
