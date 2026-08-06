<?php

namespace App\Domains\Settings\Repositories;

use App\Domains\Settings\Models\FileFolder;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FolderRepository extends BaseRepository
{
    public function __construct(FileFolder $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?FileFolder
    {
        /** @var FileFolder|null $folder */
        $folder = $this->model->newQuery()
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })->first();

        return $folder;
    }

    public function findByIdentifierOrFail(string $identifier): FileFolder
    {
        $folder = $this->findByIdentifier($identifier);
        if (! $folder) {
            abort(404, 'Folder not found.');
        }

        return $folder;
    }

    /**
     * @return Collection<int, FileFolder>
     */
    public function tree(): Collection
    {
        return $this->model->newQuery()
            ->with(['children' => fn ($q) => $q->orderBy('name')])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, FileFolder>
     */
    public function filtered(array $filters = []): Collection
    {
        $query = $this->model->newQuery()->withCount('media')->orderBy('name');

        if (array_key_exists('parent_id', $filters)) {
            if ($filters['parent_id'] === null || $filters['parent_id'] === '' || $filters['parent_id'] === 'root') {
                $query->whereNull('parent_id');
            } else {
                $parent = $this->findByIdentifier((string) $filters['parent_id']);
                $query->where('parent_id', $parent?->id);
            }
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->get();
    }
}
