<?php

namespace App\Domains\Content\Repositories;

use App\Domains\Content\Models\MediaFolder;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class MediaFolderRepository extends BaseRepository
{
    public function __construct(MediaFolder $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?MediaFolder
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var MediaFolder|null $folder */
        $folder = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)->orWhere('slug', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $folder;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): MediaFolder
    {
        $folder = $this->findByIdentifier($identifier, $withTrashed);
        if (! $folder) {
            abort(404, 'Media folder not found.');
        }

        return $folder;
    }

    /**
     * @return Collection<int, MediaFolder>
     */
    public function listAll(bool $activeOnly = false): Collection
    {
        $query = $this->model->newQuery()
            ->with(['parent:id,uuid,name', 'creator:id,uuid,full_name,email'])
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * @return Collection<int, MediaFolder>
     */
    public function tree(bool $activeOnly = true): Collection
    {
        $query = $this->model->newQuery()
            ->whereNull('parent_id')
            ->with(['children' => function ($builder) use ($activeOnly): void {
                $builder->orderBy('sort_order')->orderBy('name');
                if ($activeOnly) {
                    $builder->where('is_active', true);
                }
                $builder->with(['children' => function ($nested) use ($activeOnly): void {
                    $nested->orderBy('sort_order')->orderBy('name');
                    if ($activeOnly) {
                        $nested->where('is_active', true);
                    }
                }]);
            }])
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFolder(array $data): MediaFolder
    {
        /** @var MediaFolder $folder */
        $folder = $this->model->newQuery()->create($data);

        return $folder->fresh(['parent:id,uuid,name', 'creator:id,uuid,full_name,email']) ?? $folder;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateFolder(MediaFolder $folder, array $data): MediaFolder
    {
        $folder->fill($data);
        $folder->save();

        return $folder->fresh(['parent:id,uuid,name', 'creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']) ?? $folder;
    }

    public function uniqueSlug(?int $parentId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'folder';
        $candidate = $base;
        $suffix = 2;

        while ($this->slugExists($parentId, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function slugExists(?int $parentId, string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('slug', $slug)
            ->when($parentId === null, fn (Builder $q) => $q->whereNull('parent_id'), fn (Builder $q) => $q->where('parent_id', $parentId));

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
