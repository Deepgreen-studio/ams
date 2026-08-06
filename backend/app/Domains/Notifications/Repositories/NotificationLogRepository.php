<?php

namespace App\Domains\Notifications\Repositories;

use App\Domains\Notifications\Models\NotificationLog;
use App\Domains\Notifications\Models\NotificationPreference;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class NotificationLogRepository extends BaseRepository
{
    public function __construct(NotificationLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $query = $this->model->newQuery()
            ->with(['notifiable', 'notification', 'company'])
            ->latest('id');

        if (! blank($filters['event_key'] ?? null)) {
            $query->where('event_key', $filters['event_key']);
        }
        if (! blank($filters['channel'] ?? null)) {
            $query->where('channel', $filters['channel']);
        }
        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('body_preview', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'sent' => $this->model->newQuery()->where('status', 'sent')->count(),
            'failed' => $this->model->newQuery()->where('status', 'failed')->count(),
            'skipped' => $this->model->newQuery()->where('status', 'skipped')->count(),
            'queued' => $this->model->newQuery()->where('status', 'queued')->count(),
        ];
    }
}
