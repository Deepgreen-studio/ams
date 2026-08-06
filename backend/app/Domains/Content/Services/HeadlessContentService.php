<?php

namespace App\Domains\Content\Services;

use App\Domains\Content\Enums\ContentStatusSlug;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentCategory;
use App\Domains\Content\Models\ContentTag;
use App\Domains\Content\Repositories\ContentCategoryRepository;
use App\Domains\Content\Repositories\ContentRepository;
use App\Domains\Content\Repositories\ContentTagRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HeadlessContentService
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ContentCategoryRepository $contentCategoryRepository,
        private readonly ContentTagRepository $contentTagRepository,
        private readonly CmsSeoService $cmsSeoService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listPublished(array $filters = []): LengthAwarePaginator
    {
        return $this->contentRepository->paginateFiltered($this->publishedFilters($filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listPrivate(array $filters = []): LengthAwarePaginator
    {
        return $this->contentRepository->paginateFiltered($filters);
    }

    public function findPublished(string $identifier, ?string $type = null): Content
    {
        $content = $this->resolveContent($identifier, $type);

        if (! $this->isPubliclyVisible($content)) {
            abort(404, 'Content not found.');
        }

        return $content->loadMissing($this->deliveryRelations());
    }

    public function findPrivate(string $identifier, ?string $type = null): Content
    {
        return $this->resolveContent($identifier, $type)->loadMissing($this->deliveryRelations());
    }

    public function showPublished(string $identifier, ?string $type = null, bool $trackView = true): Content
    {
        $content = $this->findPublished($identifier, $type);

        if ($trackView) {
            $this->incrementViewCount($content);
            $content->refresh();
        }

        return $content->loadMissing($this->deliveryRelations());
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function searchPublished(array $filters = []): LengthAwarePaginator
    {
        $filters = $this->publishedFilters($filters);

        if (empty($filters['search']) && ! empty($filters['q'])) {
            $filters['search'] = $filters['q'];
        }

        return $this->contentRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function searchPrivate(array $filters = []): LengthAwarePaginator
    {
        if (empty($filters['search']) && ! empty($filters['q'])) {
            $filters['search'] = $filters['q'];
        }

        return $this->contentRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function featured(array $filters = [], bool $publishedOnly = true): LengthAwarePaginator
    {
        $filters['is_featured'] = true;
        $filters['sort_by'] = $filters['sort_by'] ?? 'published_at';
        $filters['sort_dir'] = $filters['sort_dir'] ?? 'desc';

        return $publishedOnly
            ? $this->listPublished($filters)
            : $this->listPrivate($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function latest(array $filters = [], bool $publishedOnly = true): LengthAwarePaginator
    {
        $filters['sort_by'] = 'published_at';
        $filters['sort_dir'] = 'desc';

        return $publishedOnly
            ? $this->listPublished($filters)
            : $this->listPrivate($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function popular(array $filters = [], bool $publishedOnly = true): LengthAwarePaginator
    {
        $filters['sort_by'] = 'view_count';
        $filters['sort_dir'] = 'desc';

        return $publishedOnly
            ? $this->listPublished($filters)
            : $this->listPrivate($filters);
    }

    /**
     * @return Collection<int, ContentCategory>
     */
    public function categories(bool $activeOnly = true): Collection
    {
        if ($activeOnly) {
            return $this->contentCategoryRepository->listActive();
        }

        return $this->contentCategoryRepository->listFiltered([]);
    }

    public function category(string $identifier, bool $requireActive = true): ContentCategory
    {
        $category = $this->contentCategoryRepository->findByIdentifierOrFail($identifier);

        if ($requireActive && $category->is_active === false) {
            abort(404, 'Content category not found.');
        }

        return $category->loadMissing('parent:id,uuid,name,slug');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function categoryContents(string $identifier, array $filters = [], bool $publishedOnly = true): LengthAwarePaginator
    {
        $category = $this->category($identifier, requireActive: $publishedOnly);
        $filters['category'] = $category->slug;

        return $publishedOnly
            ? $this->listPublished($filters)
            : $this->listPrivate($filters);
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function tags(array $filters = [])
    {
        $filters['per_page'] = $filters['per_page'] ?? 50;

        return $this->contentTagRepository->paginateFiltered($filters);
    }

    public function tag(string $identifier): ContentTag
    {
        return $this->contentTagRepository->findByIdentifierOrFail($identifier);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function tagContents(string $identifier, array $filters = [], bool $publishedOnly = true): LengthAwarePaginator
    {
        $tag = $this->tag($identifier);
        $filters['tag'] = $tag->slug;

        return $publishedOnly
            ? $this->listPublished($filters)
            : $this->listPrivate($filters);
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    public function sitemapEntries(): array
    {
        $entries = [];
        $changefreq = (string) config('cms.sitemap.changefreq', 'weekly');
        $priority = (string) config('cms.sitemap.priority', '0.7');

        $contents = $this->contentRepository->filteredQuery($this->publishedFilters([
            'sort_by' => 'updated_at',
            'sort_dir' => 'desc',
        ]))
            ->with(['type:id,uuid,name,slug'])
            ->limit(5000)
            ->get();

        foreach ($contents as $content) {
            /** @var Content $content */
            $entries[] = [
                'loc' => $this->cmsSeoService->canonicalUrl($content),
                'lastmod' => $content->updated_at?->toAtomString(),
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        }

        if (config('cms.sitemap.include_categories')) {
            foreach ($this->contentCategoryRepository->listActive() as $category) {
                $entries[] = [
                    'loc' => rtrim((string) config('cms.site_url'), '/').'/category/'.$category->slug,
                    'lastmod' => $category->updated_at?->toAtomString(),
                    'changefreq' => $changefreq,
                    'priority' => '0.5',
                ];
            }
        }

        if (config('cms.sitemap.include_tags')) {
            $tags = $this->contentTagRepository->paginateFiltered(['per_page' => 1000])->getCollection();
            foreach ($tags as $tag) {
                $entries[] = [
                    'loc' => rtrim((string) config('cms.site_url'), '/').'/tag/'.$tag->slug,
                    'lastmod' => $tag->updated_at?->toAtomString(),
                    'changefreq' => $changefreq,
                    'priority' => '0.4',
                ];
            }
        }

        return $entries;
    }

    public function seoPayload(Content $content): array
    {
        return $this->cmsSeoService->buildForContent($content->loadMissing($this->deliveryRelations()));
    }

    public function incrementViewCount(Content $content): void
    {
        DB::table('contents')
            ->where('id', $content->id)
            ->update([
                'view_count' => DB::raw('view_count + 1'),
                'last_viewed_at' => now(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function publishedFilters(array $filters): array
    {
        $filters['status'] = ContentStatusSlug::Published->value;

        return $filters;
    }

    protected function resolveContent(string $identifier, ?string $type = null): Content
    {
        $byId = $this->contentRepository->findByIdentifier($identifier);
        if ($byId) {
            return $byId;
        }

        $query = Content::query()->where('slug', $identifier);

        if ($type) {
            $query->whereHas('type', function ($builder) use ($type): void {
                $builder->where('slug', $type)->orWhere('uuid', $type);
            });
        }

        /** @var Content|null $content */
        $content = $query->first();

        if (! $content) {
            abort(404, 'Content not found.');
        }

        return $content;
    }

    protected function isPubliclyVisible(Content $content): bool
    {
        $content->loadMissing('status');

        if ($content->status?->slug !== ContentStatusSlug::Published->value) {
            return false;
        }

        if ($content->published_at && $content->published_at->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * @return list<string>
     */
    protected function deliveryRelations(): array
    {
        return [
            'type:id,uuid,name,slug',
            'status:id,uuid,name,slug,color',
            'category:id,uuid,name,slug',
            'categories:id,uuid,name,slug',
            'tags:id,uuid,name,slug',
            'creator:id,uuid,full_name,email',
            'publisher:id,uuid,full_name,email',
        ];
    }
}
