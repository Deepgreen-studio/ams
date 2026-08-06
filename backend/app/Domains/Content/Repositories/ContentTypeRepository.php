<?php

namespace App\Domains\Content\Repositories;

use App\Domains\Content\Models\ContentType;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ContentTypeRepository extends BaseRepository
{
    public function __construct(ContentType $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?ContentType
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ContentType|null $type */
        $type = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)
                ->orWhere('slug', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $type;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): ContentType
    {
        $type = $this->findByIdentifier($identifier, $withTrashed);

        if (! $type) {
            abort(404, 'Content type not found.');
        }

        return $type;
    }

    public function findBySlug(string $slug): ?ContentType
    {
        /** @var ContentType|null $type */
        $type = $this->model->newQuery()->where('slug', $slug)->first();

        return $type;
    }

    /**
     * @return Collection<int, ContentType>
     */
    public function listActive(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('slug', $slug)
            ->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createType(array $data): ContentType
    {
        /** @var ContentType $type */
        $type = $this->model->newQuery()->create($data);

        return $type->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateType(ContentType $type, array $data): ContentType
    {
        $type->fill($data);
        $type->save();

        return $type->refresh();
    }
}
