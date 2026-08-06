<?php

namespace App\Domains\Content\Repositories;

use App\Domains\Content\Models\MediaLibraryItem;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MediaLibraryRepository extends BaseRepository
{
    public function __construct(MediaLibraryItem $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?MediaLibraryItem
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var MediaLibraryItem|null $item */
        $item = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $item;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): MediaLibraryItem
    {
        $item = $this->findByIdentifier($identifier, $withTrashed);
        if (! $item) {
            abort(404, 'Media item not found.');
        }

        return $item;
    }

    public function findCurrentByGroup(string $groupUuid): ?MediaLibraryItem
    {
        /** @var MediaLibraryItem|null $item */
        $item = $this->model->newQuery()
            ->where('media_group_uuid', $groupUuid)
            ->where('is_current', true)
            ->first();

        return $item;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 24), 100));
        $query = $this->model->newQuery()
            ->with(['folder:id,uuid,name', 'uploader:id,uuid,full_name,email']);

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (! array_key_exists('include_versions', $filters) || ! filter_var($filters['include_versions'], FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_current', true);
        }

        if (! empty($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        } elseif (($filters['root'] ?? null) === true || ($filters['root'] ?? null) === '1' || ($filters['folder'] ?? null) === 'root') {
            $query->whereNull('folder_id');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['extension'])) {
            $query->where('extension', strtolower((string) $filters['extension']));
        }

        if (! empty($filters['mime_type'])) {
            $query->where('mime_type', 'like', $filters['mime_type'].'%');
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhere('filename', 'like', "%{$search}%")
                    ->orWhere('extension', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%")
                    ->orWhere('caption', 'like', "%{$search}%");
            });
        }

        $sortBy = in_array(($filters['sort_by'] ?? ''), ['name', 'original_name', 'size', 'created_at', 'type', 'extension'], true)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, MediaLibraryItem>
     */
    public function versionsForGroup(string $groupUuid): Collection
    {
        return $this->model->newQuery()
            ->where('media_group_uuid', $groupUuid)
            ->with(['uploader:id,uuid,full_name,email'])
            ->orderByDesc('version')
            ->get();
    }

    public function nextVersionNumber(string $groupUuid): int
    {
        $max = (int) $this->model->newQuery()
            ->where('media_group_uuid', $groupUuid)
            ->max('version');

        return $max + 1;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createItem(array $data): MediaLibraryItem
    {
        /** @var MediaLibraryItem $item */
        $item = $this->model->newQuery()->create($data);

        return $item->fresh(['folder:id,uuid,name', 'uploader:id,uuid,full_name,email']) ?? $item;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(MediaLibraryItem $item, array $data): MediaLibraryItem
    {
        $item->fill($data);
        $item->save();

        return $item->fresh(['folder:id,uuid,name', 'uploader:id,uuid,full_name,email']) ?? $item;
    }

    public function markGroupNotCurrent(string $groupUuid): void
    {
        $this->model->newQuery()
            ->where('media_group_uuid', $groupUuid)
            ->where('is_current', true)
            ->update(['is_current' => false]);
    }
}
