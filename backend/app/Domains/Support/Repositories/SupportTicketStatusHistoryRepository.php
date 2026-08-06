<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Models\SupportTicketStatusHistory;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class SupportTicketStatusHistoryRepository extends BaseRepository
{
    public function __construct(SupportTicketStatusHistory $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): SupportTicketStatusHistory
    {
        /** @var SupportTicketStatusHistory $history */
        $history = $this->model->newQuery()->create($data);

        return $history->load('actor:id,uuid,full_name,email');
    }

    /**
     * @return Collection<int, SupportTicketStatusHistory>
     */
    public function forTicket(int $ticketId): Collection
    {
        return $this->model->newQuery()
            ->with('actor:id,uuid,full_name,email')
            ->where('support_ticket_id', $ticketId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function recordForTicket(
        SupportTicket $ticket,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        int $actorId,
        ?string $comments = null,
        array $metadata = []
    ): SupportTicketStatusHistory {
        return $this->record([
            'support_ticket_id' => $ticket->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action' => $action,
            'acted_by' => $actorId,
            'comments' => $comments,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
