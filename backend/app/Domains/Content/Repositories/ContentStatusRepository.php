<?php

namespace App\Domains\Content\Repositories;

use App\Domains\Content\Models\ContentStatus;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ContentStatusRepository extends BaseRepository
{
    public function __construct(ContentStatus $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?ContentStatus
    {
        /** @var ContentStatus|null $status */
        $status = $this->model->newQuery()->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('slug', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $status;
    }

    public function findByIdentifierOrFail(string $identifier): ContentStatus
    {
        $status = $this->findByIdentifier($identifier);

        if (! $status) {
            abort(404, 'Content status not found.');
        }

        return $status;
    }

    public function findBySlug(string $slug): ?ContentStatus
    {
        /** @var ContentStatus|null $status */
        $status = $this->model->newQuery()->where('slug', $slug)->first();

        return $status;
    }

    public function findBySlugOrFail(string $slug): ContentStatus
    {
        $status = $this->findBySlug($slug);

        if (! $status) {
            abort(404, 'Content status not found.');
        }

        return $status;
    }

    /**
     * @return Collection<int, ContentStatus>
     */
    public function listAll(): Collection
    {
        return $this->model->newQuery()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
