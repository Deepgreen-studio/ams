<?php

namespace App\Domains\Content\Repositories;

use App\Domains\Content\Models\ContentWorkflowHistory;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class ContentWorkflowHistoryRepository extends BaseRepository
{
    public function __construct(ContentWorkflowHistory $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createHistory(array $data): ContentWorkflowHistory
    {
        /** @var ContentWorkflowHistory $history */
        $history = $this->model->newQuery()->create($data);

        return $history->fresh(['actor:id,uuid,full_name,email']) ?? $history;
    }

    /**
     * @return Collection<int, ContentWorkflowHistory>
     */
    public function forContent(int $contentId): Collection
    {
        return $this->model->newQuery()
            ->where('content_id', $contentId)
            ->with(['actor:id,uuid,full_name,email'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
