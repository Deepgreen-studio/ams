<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Models\KnowledgeTag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KnowledgeTagRepository
{
    public function findByIdentifierOrFail(string $identifier): KnowledgeTag
    {
        return KnowledgeTag::query()
            ->where(function (Builder $query) use ($identifier): void {
                $query->where('uuid', $identifier)->orWhere('slug', $identifier);
                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();
    }

    /**
     * @return Collection<int, KnowledgeTag>
     */
    public function all(bool $activeOnly = false): Collection
    {
        $query = KnowledgeTag::query()->withCount('articles')->orderBy('sort_order')->orderBy('name');
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): KnowledgeTag
    {
        return KnowledgeTag::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(KnowledgeTag $tag, array $data): KnowledgeTag
    {
        $tag->fill($data);
        $tag->save();

        return $tag->refresh();
    }

    /**
     * @param  list<string>  $namesOrUuids
     * @return list<int>
     */
    public function resolveTagIds(array $namesOrUuids, ?int $actorId = null): array
    {
        $ids = [];

        foreach ($namesOrUuids as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $tag = KnowledgeTag::query()
                ->where('uuid', $value)
                ->orWhere('slug', Str::slug($value))
                ->orWhere('name', $value)
                ->first();

            if (! $tag) {
                $tag = $this->create([
                    'name' => $value,
                    'slug' => $this->uniqueSlug($value),
                    'is_active' => true,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }

            $ids[] = $tag->id;
        }

        return array_values(array_unique($ids));
    }

    public function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'tag';
        $slug = $base;
        $i = 1;

        while (
            KnowledgeTag::query()
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
