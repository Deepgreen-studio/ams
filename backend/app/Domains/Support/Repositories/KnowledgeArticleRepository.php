<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Enums\KnowledgeArticleStatus;
use App\Domains\Support\Models\KnowledgeArticle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KnowledgeArticleRepository
{
    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): KnowledgeArticle
    {
        $query = KnowledgeArticle::query()->when($withTrashed, fn (Builder $q) => $q->withTrashed());

        return $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)->orWhere('slug', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = KnowledgeArticle::query()->with([
            'category:id,uuid,name,slug',
            'tags:id,uuid,name,slug',
            'author:id,uuid,full_name,email',
            'content:id,uuid,title,slug,version,published_at',
        ]);

        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('summary', 'like', '%'.$search.'%')
                    ->orWhere('body', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        if (! blank($filters['type'] ?? null)) {
            $query->where('type', $filters['type']);
        }

        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (! blank($filters['category_id'] ?? null)) {
            $query->where('knowledge_category_id', $filters['category_id']);
        }

        if (! blank($filters['tag_id'] ?? null)) {
            $query->whereHas('tags', fn (Builder $q) => $q->where('knowledge_tags.id', $filters['tag_id']));
        }

        if (array_key_exists('is_featured', $filters) && $filters['is_featured'] !== '' && $filters['is_featured'] !== null) {
            $query->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
        }

        if (($filters['published_only'] ?? false) === true) {
            $query->where('status', KnowledgeArticleStatus::Published->value)
                ->whereNotNull('published_at');
        }

        $requestedSort = (string) ($filters['sort_by'] ?? 'updated_at');
        $sortBy = in_array($requestedSort, [
            'title', 'created_at', 'updated_at', 'published_at', 'view_count', 'helpful_count', 'sort_order',
        ], true) ? $requestedSort : 'updated_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    /**
     * @return Collection<int, KnowledgeArticle>
     */
    public function relatedCandidates(KnowledgeArticle $article, int $limit = 5): Collection
    {
        $tagIds = $article->tags()->pluck('knowledge_tags.id');

        return KnowledgeArticle::query()
            ->with(['category:id,uuid,name,slug', 'tags:id,uuid,name,slug'])
            ->where('id', '!=', $article->id)
            ->where('status', KnowledgeArticleStatus::Published->value)
            ->where(function (Builder $query) use ($article, $tagIds): void {
                if ($article->knowledge_category_id) {
                    $query->where('knowledge_category_id', $article->knowledge_category_id);
                }
                if ($tagIds->isNotEmpty()) {
                    $query->orWhereHas('tags', fn (Builder $q) => $q->whereIn('knowledge_tags.id', $tagIds));
                }
                $query->orWhere('type', $article->type?->value ?? $article->type);
            })
            ->orderByDesc('helpful_count')
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => KnowledgeArticle::query()->count(),
            'published' => KnowledgeArticle::query()->where('status', KnowledgeArticleStatus::Published->value)->count(),
            'draft' => KnowledgeArticle::query()->where('status', KnowledgeArticleStatus::Draft->value)->count(),
            'archived' => KnowledgeArticle::query()->where('status', KnowledgeArticleStatus::Archived->value)->count(),
            'linked_to_cms' => KnowledgeArticle::query()->whereNotNull('content_id')->count(),
            'featured' => KnowledgeArticle::query()->where('is_featured', true)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): KnowledgeArticle
    {
        return KnowledgeArticle::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(KnowledgeArticle $article, array $data): KnowledgeArticle
    {
        $article->fill($data);
        $article->save();

        return $article->refresh();
    }

    public function uniqueSlug(string $title, string $type, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $i = 1;

        while (
            KnowledgeArticle::query()
                ->where('type', $type)
                ->where('slug', $slug)
                ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
