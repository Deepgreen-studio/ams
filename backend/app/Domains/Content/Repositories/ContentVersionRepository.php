<?php

namespace App\Domains\Content\Repositories;

use App\Domains\Content\Models\ContentVersion;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ContentVersionRepository extends BaseRepository
{
    public function __construct(ContentVersion $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createVersion(array $data): ContentVersion
    {
        /** @var ContentVersion $version */
        $version = $this->model->newQuery()->create($data);

        return $version->fresh(['creator:id,uuid,full_name,email']) ?? $version;
    }

    /**
     * @return Collection<int, ContentVersion>
     */
    public function versionsForContent(int $contentId): Collection
    {
        return $this->model->newQuery()
            ->where('content_id', $contentId)
            ->with(['creator:id,uuid,full_name,email'])
            ->orderByDesc('version')
            ->get();
    }

    public function findForContent(int $contentId, string $identifier): ContentVersion
    {
        /** @var ContentVersion|null $version */
        $version = $this->model->newQuery()
            ->where('content_id', $contentId)
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('version', (int) $identifier)
                        ->orWhere('id', (int) $identifier);
                }
            })
            ->with(['creator:id,uuid,full_name,email'])
            ->first();

        if (! $version) {
            abort(404, 'Content version not found.');
        }

        return $version;
    }

    public function nextVersionNumber(int $contentId): int
    {
        $max = (int) $this->model->newQuery()
            ->where('content_id', $contentId)
            ->max('version');

        return $max + 1;
    }
}
