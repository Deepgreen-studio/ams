<?php

namespace App\Domains\Notifications\Repositories;

use App\Domains\Notifications\Enums\NotificationTemplateApprovalStatus;
use App\Domains\Notifications\Models\NotificationTemplateApproval;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class NotificationTemplateApprovalRepository extends BaseRepository
{
    public function __construct(NotificationTemplateApproval $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifierOrFail(string $identifier): NotificationTemplateApproval
    {
        /** @var NotificationTemplateApproval|null $approval */
        $approval = $this->model->newQuery()
            ->with(['template', 'version', 'requester', 'reviewer'])
            ->where('uuid', $identifier)
            ->when(ctype_digit($identifier), fn ($q) => $q->orWhere('id', (int) $identifier))
            ->first();

        if (! $approval) {
            abort(404, 'Notification template approval not found.');
        }

        return $approval;
    }

    public function cancelPendingForTemplate(int $templateId): void
    {
        $this->model->newQuery()
            ->where('notification_template_id', $templateId)
            ->where('status', NotificationTemplateApprovalStatus::Pending->value)
            ->update([
                'status' => NotificationTemplateApprovalStatus::Cancelled->value,
                'decided_at' => now(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $query = $this->model->newQuery()
            ->with([
                'template:id,uuid,name,channel,locale,event_key,workflow_status',
                'version:id,uuid,version,status',
                'requester:id,uuid,full_name,email',
                'reviewer:id,uuid,full_name,email',
            ])
            ->latest('id');

        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', NotificationTemplateApprovalStatus::Pending->value);
        }

        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->whereHas('template', function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
