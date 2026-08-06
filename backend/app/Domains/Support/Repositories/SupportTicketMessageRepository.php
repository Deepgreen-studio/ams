<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Models\SupportTicketMessage;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SupportTicketMessageRepository extends BaseRepository
{
    public function __construct(SupportTicketMessage $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?SupportTicketMessage
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var SupportTicketMessage|null $message */
        $message = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $message;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): SupportTicketMessage
    {
        $message = $this->findByIdentifier($identifier, $withTrashed);

        if (! $message) {
            abort(404, 'Ticket message not found.');
        }

        return $message;
    }

    /**
     * @return Collection<int, SupportTicketMessage>
     */
    public function forTicket(int $ticketId, ?array $visibilities = null): Collection
    {
        $query = $this->model->newQuery()
            ->with([
                'author:id,uuid,full_name,email',
                'attachments',
                'reads:id,ticket_message_id,user_id,read_at',
            ])
            ->where('support_ticket_id', $ticketId)
            ->orderBy('created_at')
            ->orderBy('id');

        if ($visibilities !== null) {
            $query->whereIn('visibility', $visibilities);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createMessage(array $data): SupportTicketMessage
    {
        /** @var SupportTicketMessage $message */
        $message = $this->model->newQuery()->create($data);

        return $message->fresh([
            'author:id,uuid,full_name,email',
            'attachments',
            'reads',
        ]) ?? $message;
    }

    public function unreadCountForUser(int $ticketId, int $userId): int
    {
        return $this->model->newQuery()
            ->where('support_ticket_id', $ticketId)
            ->where(function (Builder $builder) use ($userId): void {
                $builder->whereNull('author_id')
                    ->orWhere('author_id', '!=', $userId);
            })
            ->whereNotExists(function ($query) use ($userId): void {
                $query->select(DB::raw(1))
                    ->from('ticket_message_reads')
                    ->whereColumn('ticket_message_reads.ticket_message_id', 'ticket_messages.id')
                    ->where('ticket_message_reads.user_id', $userId);
            })
            ->count();
    }
}
