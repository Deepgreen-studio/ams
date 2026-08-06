<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Compliance\Models\BreachNotification;
use App\Domains\Compliance\Models\DataBreach;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BreachNotificationRepository extends BaseRepository
{
    public function __construct(BreachNotification $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?BreachNotification
    {
        /** @var BreachNotification|null $notification */
        $notification = $this->model->newQuery()
            ->where('uuid', $identifier)
            ->when(ctype_digit($identifier), fn ($q) => $q->orWhere('id', (int) $identifier))
            ->first();

        return $notification;
    }

    public function findByIdentifierOrFail(string $identifier): BreachNotification
    {
        $notification = $this->findByIdentifier($identifier);

        if (! $notification) {
            abort(404, 'Breach notification not found.');
        }

        return $notification;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForBreach(DataBreach $breach, array $data): BreachNotification
    {
        $data['data_breach_id'] = $breach->id;

        /** @var BreachNotification $notification */
        $notification = $this->model->newQuery()->create($data);

        return $notification->fresh(['sender', 'dataBreach']) ?? $notification;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateNotification(BreachNotification $notification, array $data): BreachNotification
    {
        $notification->fill($data);
        $notification->save();

        return $notification->refresh()->load(['sender', 'dataBreach']);
    }

    /**
     * @return Collection<int, BreachNotification>
     */
    public function forBreach(int $breachId): Collection
    {
        return $this->model->newQuery()
            ->with(['sender:id,uuid,full_name,email'])
            ->where('data_breach_id', $breachId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        $query = $this->model->newQuery()
            ->with([
                'sender:id,uuid,full_name,email',
                'dataBreach:id,uuid,breach_number,title,company_id,status,severity',
                'dataBreach.company:id,uuid,company_name',
            ]);

        if (! empty($filters['company_id'])) {
            $query->whereHas('dataBreach', function (Builder $builder) use ($filters): void {
                $builder->where('company_id', (int) $filters['company_id']);
            });
        }

        if (! empty($filters['data_breach_id'])) {
            $query->where('data_breach_id', (int) $filters['data_breach_id']);
        }

        if (! empty($filters['notification_type'])) {
            $query->where('notification_type', $filters['notification_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
