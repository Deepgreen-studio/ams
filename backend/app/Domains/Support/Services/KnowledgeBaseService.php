<?php

namespace App\Domains\Support\Services;

use App\Domains\Content\Repositories\ContentRepository;
use App\Domains\Support\Enums\KnowledgeArticleStatus;
use App\Domains\Support\Enums\KnowledgeArticleType;
use App\Domains\Support\Models\KnowledgeArticle;
use App\Domains\Support\Models\KnowledgeArticleFeedback;
use App\Domains\Support\Models\KnowledgeArticleVersion;
use App\Domains\Support\Models\KnowledgeCategory;
use App\Domains\Support\Models\KnowledgeTag;
use App\Domains\Support\Repositories\KnowledgeArticleRepository;
use App\Domains\Support\Repositories\KnowledgeCategoryRepository;
use App\Domains\Support\Repositories\KnowledgeTagRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KnowledgeBaseService
{
    public function __construct(
        private readonly KnowledgeArticleRepository $articleRepository,
        private readonly KnowledgeCategoryRepository $categoryRepository,
        private readonly KnowledgeTagRepository $tagRepository,
        private readonly ContentRepository $contentRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        return [
            'statistics' => $this->articleRepository->statistics(),
            'featured' => KnowledgeArticle::query()
                ->with(['category:id,uuid,name,slug', 'tags:id,uuid,name,slug'])
                ->where('status', KnowledgeArticleStatus::Published->value)
                ->where('is_featured', true)
                ->orderByDesc('published_at')
                ->limit(6)
                ->get(),
            'latest' => KnowledgeArticle::query()
                ->with(['category:id,uuid,name,slug'])
                ->where('status', KnowledgeArticleStatus::Published->value)
                ->orderByDesc('published_at')
                ->limit(8)
                ->get(),
            'popular' => KnowledgeArticle::query()
                ->with(['category:id,uuid,name,slug'])
                ->where('status', KnowledgeArticleStatus::Published->value)
                ->orderByDesc('view_count')
                ->limit(8)
                ->get(),
            'types' => collect(KnowledgeArticleType::cases())->map(fn (KnowledgeArticleType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'count' => KnowledgeArticle::query()
                    ->where('type', $type->value)
                    ->where('status', KnowledgeArticleStatus::Published->value)
                    ->count(),
            ])->values()->all(),
            'categories' => $this->categoryRepository->tree(activeOnly: true),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listArticles(array $filters = []): LengthAwarePaginator
    {
        if (! blank($filters['category'] ?? null)) {
            $category = $this->categoryRepository->findByIdentifierOrFail((string) $filters['category']);
            $filters['category_id'] = $category->id;
        }

        if (! blank($filters['tag'] ?? null)) {
            $tag = $this->tagRepository->findByIdentifierOrFail((string) $filters['tag']);
            $filters['tag_id'] = $tag->id;
        }

        return $this->articleRepository->paginate($filters);
    }

    public function findArticle(string $identifier, bool $withTrashed = false): KnowledgeArticle
    {
        return $this->articleRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function showArticle(string $identifier, bool $trackView = false): KnowledgeArticle
    {
        $article = $this->articleRepository->findByIdentifierOrFail($identifier);
        $article->load([
            'category:id,uuid,name,slug,description',
            'tags:id,uuid,name,slug',
            'author:id,uuid,full_name,email',
            'publisher:id,uuid,full_name,email',
            'content:id,uuid,title,slug,summary,body,body_format,featured_image,version,published_at,content_type_id',
            'content.type:id,uuid,name,slug',
            'relatedArticles:id,uuid,title,slug,type,summary,status',
            'feedback' => fn ($q) => $q->latest('id'),
        ]);

        if ($article->sync_from_cms && $article->content) {
            $article = $this->applyCmsBody($article);
        }

        if ($trackView && $article->status === KnowledgeArticleStatus::Published) {
            $article->increment('view_count');
            $article->refresh();
        }

        return $article;
    }

    /**
     * @return Collection<int, KnowledgeArticle>
     */
    public function relatedArticles(string $identifier, int $limit = 5): Collection
    {
        $article = $this->articleRepository->findByIdentifierOrFail($identifier);
        $manual = $article->relatedArticles()
            ->where('status', KnowledgeArticleStatus::Published->value)
            ->limit($limit)
            ->get();

        if ($manual->isNotEmpty()) {
            return $manual;
        }

        return $this->articleRepository->relatedCandidates($article, $limit);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createArticle(array $data, User $actor): KnowledgeArticle
    {
        return DB::transaction(function () use ($data, $actor): KnowledgeArticle {
            $payload = $this->prepareArticlePayload($data, $actor, isCreate: true);
            $article = $this->articleRepository->create($payload);
            $this->syncTaxonomy($article, $data, $actor);
            $this->syncRelated($article, $data['related_article_ids'] ?? []);
            $this->createVersion($article, $actor, 'Initial version');

            return $this->showArticle($article->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateArticle(string $identifier, array $data, User $actor): KnowledgeArticle
    {
        return DB::transaction(function () use ($identifier, $data, $actor): KnowledgeArticle {
            $article = $this->articleRepository->findByIdentifierOrFail($identifier);
            $payload = $this->prepareArticlePayload($data, $actor, isCreate: false, article: $article);

            $bodyChanged = array_key_exists('body', $payload) && $payload['body'] !== $article->body;
            $titleChanged = array_key_exists('title', $payload) && $payload['title'] !== $article->title;
            $statusChanged = array_key_exists('status', $payload)
                && $payload['status'] !== ($article->status?->value ?? $article->status);

            if ($bodyChanged || $titleChanged || $statusChanged) {
                $payload['version'] = ((int) $article->version) + 1;
            }

            $article = $this->articleRepository->update($article, $payload);
            $this->syncTaxonomy($article, $data, $actor);

            if (array_key_exists('related_article_ids', $data)) {
                $this->syncRelated($article, $data['related_article_ids'] ?? []);
            }

            if ($bodyChanged || $titleChanged || $statusChanged) {
                $this->createVersion($article, $actor, $data['version_reason'] ?? 'Article updated');
            }

            return $this->showArticle($article->uuid);
        });
    }

    public function publishArticle(string $identifier, User $actor): KnowledgeArticle
    {
        return $this->updateArticle($identifier, [
            'status' => KnowledgeArticleStatus::Published->value,
            'version_reason' => 'Published',
        ], $actor);
    }

    public function archiveArticle(string $identifier, User $actor): KnowledgeArticle
    {
        return $this->updateArticle($identifier, [
            'status' => KnowledgeArticleStatus::Archived->value,
            'version_reason' => 'Archived',
        ], $actor);
    }

    public function deleteArticle(string $identifier, User $actor): void
    {
        $article = $this->articleRepository->findByIdentifierOrFail($identifier);
        $this->articleRepository->update($article, ['updated_by' => $actor->id]);
        $article->delete();
    }

    public function linkCmsContent(string $identifier, string $contentIdentifier, User $actor, bool $sync = true): KnowledgeArticle
    {
        $article = $this->articleRepository->findByIdentifierOrFail($identifier);
        $content = $this->contentRepository->findByIdentifierOrFail($contentIdentifier);

        $payload = [
            'content_id' => $content->id,
            'sync_from_cms' => $sync,
            'updated_by' => $actor->id,
        ];

        if ($sync) {
            $payload['title'] = $content->title ?: $article->title;
            $payload['summary'] = $content->summary ?: $content->excerpt ?: $article->summary;
            $payload['body'] = $content->body;
            $payload['body_format'] = $content->body_format?->value ?? $content->body_format ?? 'html';
            $payload['featured_image'] = $content->featured_image ?: $article->featured_image;
            $payload['version'] = ((int) $article->version) + 1;
        }

        $article = $this->articleRepository->update($article, $payload);

        if ($sync) {
            $this->createVersion($article, $actor, 'Synced from CMS content '.$content->uuid);
        }

        return $this->showArticle($article->uuid);
    }

    public function unlinkCmsContent(string $identifier, User $actor): KnowledgeArticle
    {
        $article = $this->articleRepository->findByIdentifierOrFail($identifier);

        return $this->articleRepository->update($article, [
            'content_id' => null,
            'sync_from_cms' => false,
            'updated_by' => $actor->id,
        ]);
    }

    /**
     * @return Collection<int, KnowledgeArticleVersion>
     */
    public function versions(string $identifier): Collection
    {
        $article = $this->articleRepository->findByIdentifierOrFail($identifier);

        return $article->versions()->with('creator:id,uuid,full_name,email')->get();
    }

    public function restoreVersion(string $identifier, string $versionIdentifier, User $actor): KnowledgeArticle
    {
        return DB::transaction(function () use ($identifier, $versionIdentifier, $actor): KnowledgeArticle {
            $article = $this->articleRepository->findByIdentifierOrFail($identifier);
            $version = KnowledgeArticleVersion::query()
                ->where('knowledge_article_id', $article->id)
                ->where(function ($q) use ($versionIdentifier): void {
                    $q->where('uuid', $versionIdentifier);
                    if (ctype_digit($versionIdentifier)) {
                        $q->orWhere('version', (int) $versionIdentifier);
                    }
                })
                ->firstOrFail();

            $article = $this->articleRepository->update($article, [
                'title' => $version->title,
                'body' => $version->body,
                'body_format' => $version->body_format,
                'summary' => $version->summary,
                'version' => ((int) $article->version) + 1,
                'updated_by' => $actor->id,
                'sync_from_cms' => false,
            ]);

            $this->createVersion($article, $actor, 'Restored from version '.$version->version);

            return $this->showArticle($article->uuid);
        });
    }

    public function submitFeedback(string $identifier, User $user, bool $isHelpful, ?string $comment = null, ?string $ip = null): KnowledgeArticle
    {
        return DB::transaction(function () use ($identifier, $user, $isHelpful, $comment, $ip): KnowledgeArticle {
            $article = $this->articleRepository->findByIdentifierOrFail($identifier);

            $existing = KnowledgeArticleFeedback::query()
                ->where('knowledge_article_id', $article->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                $wasHelpful = (bool) $existing->is_helpful;
                if ($wasHelpful !== $isHelpful) {
                    if ($wasHelpful) {
                        $article->decrement('helpful_count');
                        $article->increment('not_helpful_count');
                    } else {
                        $article->decrement('not_helpful_count');
                        $article->increment('helpful_count');
                    }
                }
                $existing->update([
                    'is_helpful' => $isHelpful,
                    'comment' => $comment,
                    'ip_address' => $ip,
                ]);
            } else {
                KnowledgeArticleFeedback::query()->create([
                    'knowledge_article_id' => $article->id,
                    'user_id' => $user->id,
                    'is_helpful' => $isHelpful,
                    'comment' => $comment,
                    'ip_address' => $ip,
                ]);

                if ($isHelpful) {
                    $article->increment('helpful_count');
                } else {
                    $article->increment('not_helpful_count');
                }
            }

            return $article->fresh() ?? $article;
        });
    }

    /**
     * @return Collection<int, KnowledgeCategory>
     */
    public function listCategories(bool $tree = true): Collection
    {
        return $tree ? $this->categoryRepository->tree() : $this->categoryRepository->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data, User $actor): KnowledgeCategory
    {
        $parentId = null;
        if (! blank($data['parent_id'] ?? null)) {
            $parentId = $this->categoryRepository->findByIdentifierOrFail((string) $data['parent_id'])->id;
        }

        return $this->categoryRepository->create([
            'parent_id' => $parentId,
            'name' => $data['name'],
            'slug' => $this->categoryRepository->uniqueSlug((string) ($data['slug'] ?? $data['name'])),
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(string $identifier, array $data, User $actor): KnowledgeCategory
    {
        $category = $this->categoryRepository->findByIdentifierOrFail($identifier);
        $payload = [
            'updated_by' => $actor->id,
        ];

        foreach (['name', 'description', 'icon', 'sort_order', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (array_key_exists('parent_id', $data)) {
            $payload['parent_id'] = blank($data['parent_id'])
                ? null
                : $this->categoryRepository->findByIdentifierOrFail((string) $data['parent_id'])->id;
        }

        if (! blank($data['slug'] ?? null) || ! blank($data['name'] ?? null)) {
            $payload['slug'] = $this->categoryRepository->uniqueSlug(
                (string) ($data['slug'] ?? $data['name'] ?? $category->name),
                $category->id
            );
        }

        return $this->categoryRepository->update($category, $payload);
    }

    public function deleteCategory(string $identifier): void
    {
        $category = $this->categoryRepository->findByIdentifierOrFail($identifier);
        if ($category->articles()->exists()) {
            throw new ApiException('Cannot delete a category that still has articles.', 422);
        }
        $category->delete();
    }

    /**
     * @return Collection<int, KnowledgeTag>
     */
    public function listTags(): Collection
    {
        return $this->tagRepository->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTag(array $data, User $actor): KnowledgeTag
    {
        return $this->tagRepository->create([
            'name' => $data['name'],
            'slug' => $this->tagRepository->uniqueSlug((string) ($data['slug'] ?? $data['name'])),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTag(string $identifier, array $data, User $actor): KnowledgeTag
    {
        $tag = $this->tagRepository->findByIdentifierOrFail($identifier);
        $payload = ['updated_by' => $actor->id];

        if (array_key_exists('name', $data)) {
            $payload['name'] = $data['name'];
        }
        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }
        if (array_key_exists('sort_order', $data)) {
            $payload['sort_order'] = (int) $data['sort_order'];
        }
        if (! blank($data['slug'] ?? null) || ! blank($data['name'] ?? null)) {
            $payload['slug'] = $this->tagRepository->uniqueSlug(
                (string) ($data['slug'] ?? $data['name'] ?? $tag->name),
                $tag->id
            );
        }

        return $this->tagRepository->update($tag, $payload);
    }

    public function deleteTag(string $identifier): void
    {
        $tag = $this->tagRepository->findByIdentifierOrFail($identifier);
        $tag->articles()->detach();
        $tag->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareArticlePayload(array $data, User $actor, bool $isCreate, ?KnowledgeArticle $article = null): array
    {
        $type = (string) ($data['type'] ?? $article?->type?->value ?? KnowledgeArticleType::Article->value);
        if (! in_array($type, KnowledgeArticleType::values(), true)) {
            throw new ApiException('Invalid knowledge article type.', 422);
        }

        $payload = [
            'updated_by' => $actor->id,
        ];

        if ($isCreate) {
            $payload['created_by'] = $actor->id;
            $payload['author_id'] = $actor->id;
            $payload['type'] = $type;
            $payload['title'] = $data['title'];
            $payload['slug'] = $this->articleRepository->uniqueSlug(
                (string) ($data['slug'] ?? $data['title']),
                $type
            );
            $payload['status'] = $data['status'] ?? KnowledgeArticleStatus::Draft->value;
            $payload['body_format'] = $data['body_format'] ?? 'html';
            $payload['version'] = 1;
        }

        foreach ([
            'title', 'summary', 'body', 'body_format', 'video_url', 'featured_image',
            'sync_from_cms', 'is_featured', 'sort_order', 'status', 'type',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (array_key_exists('slug', $data) || (array_key_exists('title', $data) && array_key_exists('type', $data))) {
            $payload['slug'] = $this->articleRepository->uniqueSlug(
                (string) ($data['slug'] ?? $data['title'] ?? $article?->title),
                (string) ($payload['type'] ?? $article?->type?->value ?? $type),
                $article?->id
            );
        }

        if (array_key_exists('category_id', $data)) {
            $payload['knowledge_category_id'] = blank($data['category_id'])
                ? null
                : $this->categoryRepository->findByIdentifierOrFail((string) $data['category_id'])->id;
        }

        if (array_key_exists('content_id', $data)) {
            if (blank($data['content_id'])) {
                $payload['content_id'] = null;
                $payload['sync_from_cms'] = false;
            } else {
                $content = $this->contentRepository->findByIdentifierOrFail((string) $data['content_id']);
                $payload['content_id'] = $content->id;
                if (($data['sync_from_cms'] ?? false) === true || ($payload['sync_from_cms'] ?? false) === true) {
                    $payload['title'] = $payload['title'] ?? $content->title;
                    $payload['summary'] = $payload['summary'] ?? ($content->summary ?: $content->excerpt);
                    $payload['body'] = $payload['body'] ?? $content->body;
                    $payload['body_format'] = $payload['body_format'] ?? ($content->body_format?->value ?? 'html');
                    $payload['featured_image'] = $payload['featured_image'] ?? $content->featured_image;
                    $payload['sync_from_cms'] = true;
                }
            }
        }

        $status = $payload['status'] ?? null;
        if ($status === KnowledgeArticleStatus::Published->value) {
            $payload['published_at'] = $article?->published_at ?? now();
            $payload['published_by'] = $actor->id;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncTaxonomy(KnowledgeArticle $article, array $data, User $actor): void
    {
        if (array_key_exists('tags', $data)) {
            $tagIds = $this->tagRepository->resolveTagIds(
                array_map('strval', $data['tags'] ?? []),
                $actor->id
            );
            $article->tags()->sync($tagIds);
        }
    }

    /**
     * @param  list<string|int>  $relatedIds
     */
    protected function syncRelated(KnowledgeArticle $article, array $relatedIds): void
    {
        $sync = [];
        $order = 0;

        foreach ($relatedIds as $relatedId) {
            if (blank($relatedId)) {
                continue;
            }
            $related = $this->articleRepository->findByIdentifierOrFail((string) $relatedId);
            if ($related->id === $article->id) {
                continue;
            }
            $sync[$related->id] = ['sort_order' => $order++];
        }

        $article->relatedArticles()->sync($sync);
    }

    protected function createVersion(KnowledgeArticle $article, User $actor, string $reason): KnowledgeArticleVersion
    {
        return KnowledgeArticleVersion::query()->create([
            'knowledge_article_id' => $article->id,
            'version' => (int) $article->version,
            'title' => $article->title,
            'body' => $article->body,
            'body_format' => $article->body_format ?: 'html',
            'summary' => $article->summary,
            'status' => $article->status?->value ?? $article->status,
            'snapshot' => [
                'type' => $article->type?->value ?? $article->type,
                'content_id' => $article->content_id,
                'category_id' => $article->knowledge_category_id,
                'video_url' => $article->video_url,
                'featured_image' => $article->featured_image,
                'sync_from_cms' => (bool) $article->sync_from_cms,
            ],
            'reason' => $reason,
            'created_by' => $actor->id,
        ]);
    }

    protected function applyCmsBody(KnowledgeArticle $article): KnowledgeArticle
    {
        $content = $article->content;
        if (! $content) {
            return $article;
        }

        $article->setAttribute('title', $content->title ?: $article->title);
        $article->setAttribute('summary', $content->summary ?: $content->excerpt ?: $article->summary);
        $article->setAttribute('body', $content->body ?: $article->body);
        $article->setAttribute('body_format', $content->body_format?->value ?? $content->body_format ?? $article->body_format);
        $article->setAttribute('featured_image', $content->featured_image ?: $article->featured_image);

        return $article;
    }
}
