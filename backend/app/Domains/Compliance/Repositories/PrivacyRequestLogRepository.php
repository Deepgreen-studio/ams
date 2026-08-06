<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Compliance\Models\PrivacyRequestLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class PrivacyRequestLogRepository extends BaseRepository
{
    public function __construct(PrivacyRequestLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): PrivacyRequestLog
    {
        /** @var PrivacyRequestLog $log */
        $log = $this->model->newQuery()->create($data);

        return $log->load('actor:id,uuid,full_name,email');
    }

    /**
     * @return Collection<int, PrivacyRequestLog>
     */
    public function forRequest(int $privacyRequestId): Collection
    {
        return $this->model->newQuery()
            ->with('actor:id,uuid,full_name,email')
            ->where('privacy_request_id', $privacyRequestId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordForRequest(
        PrivacyRequest $request,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        int $actorId,
        ?string $comments = null,
        array $metadata = []
    ): PrivacyRequestLog {
        return $this->record([
            'privacy_request_id' => $request->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action' => $action,
            'acted_by' => $actorId,
            'comments' => $comments,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
