<?php

namespace App\Domains\Settings\Repositories;

use App\Domains\Settings\Models\MediaFile;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MediaRepository extends BaseRepository
{
    public function __construct(MediaFile $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?MediaFile
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var MediaFile|null $media */
        $media = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $media;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): MediaFile
    {
        $media = $this->findByIdentifier($identifier, $withTrashed);
        if (! $media) {
            abort(404, 'Media file not found.');
        }

        return $media;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 24), 100));
        $query = $this->model->newQuery()
            ->with(['folder:id,uuid,name', 'uploader:id,uuid,full_name,email']);

        if (! empty($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        } elseif (($filters['root'] ?? null) === true || ($filters['root'] ?? null) === '1') {
            $query->whereNull('folder_id');
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('original_name', 'like', "%{$search}%")
                    ->orWhere('filename', 'like', "%{$search}%")
                    ->orWhere('extension', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['mime_type'])) {
            $query->where('mime_type', 'like', $filters['mime_type'].'%');
        }

        if (! empty($filters['extension'])) {
            $query->where('extension', $filters['extension']);
        }

        $sortBy = in_array(($filters['sort_by'] ?? ''), ['original_name', 'size', 'created_at', 'mime_type'], true)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage)->withQueryString();
    }
}
