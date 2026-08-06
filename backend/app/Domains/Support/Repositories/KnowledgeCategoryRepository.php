<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Models\KnowledgeCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KnowledgeCategoryRepository
{
    public function findByIdentifierOrFail(string $identifier): KnowledgeCategory
    {
        return KnowledgeCategory::query()
            ->where(function (Builder $query) use ($identifier): void {
                $query->where('uuid', $identifier)->orWhere('slug', $identifier);
                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();
    }

    /**
     * @return Collection<int, KnowledgeCategory>
     */
    public function tree(bool $activeOnly = false): Collection
    {
        $query = KnowledgeCategory::query()
            ->withCount('articles')
            ->with(['children' => function ($q) use ($activeOnly): void {
                $q->withCount('articles')->orderBy('sort_order');
                if ($activeOnly) {
                    $q->where('is_active', true);
                }
            }])
            ->whereNull('parent_id')
            ->orderBy('sort_order');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, KnowledgeCategory>
     */
    public function all(bool $activeOnly = false): Collection
    {
        $query = KnowledgeCategory::query()->withCount('articles')->orderBy('sort_order')->orderBy('name');
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): KnowledgeCategory
    {
        return KnowledgeCategory::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(KnowledgeCategory $category, array $data): KnowledgeCategory
    {
        $category->fill($data);
        $category->save();

        return $category->refresh();
    }

    public function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 1;

        while (
            KnowledgeCategory::query()
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
