<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Models\SupportTicketAttachment;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SupportTicketAttachmentRepository extends BaseRepository
{
    public function __construct(SupportTicketAttachment $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?SupportTicketAttachment
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var SupportTicketAttachment|null $attachment */
        $attachment = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $attachment;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): SupportTicketAttachment
    {
        $attachment = $this->findByIdentifier($identifier, $withTrashed);

        if (! $attachment) {
            abort(404, 'Ticket attachment not found.');
        }

        return $attachment;
    }

    /**
     * @return Collection<int, SupportTicketAttachment>
     */
    public function forTicket(int $ticketId): Collection
    {
        return $this->model->newQuery()
            ->with(['uploader:id,uuid,full_name,email', 'message:id,uuid'])
            ->where('support_ticket_id', $ticketId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAttachment(array $data): SupportTicketAttachment
    {
        /** @var SupportTicketAttachment $attachment */
        $attachment = $this->model->newQuery()->create($data);

        return $attachment->fresh(['uploader:id,uuid,full_name,email']) ?? $attachment;
    }
}
