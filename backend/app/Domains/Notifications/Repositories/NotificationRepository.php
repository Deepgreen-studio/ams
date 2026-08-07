<?php

namespace App\Domains\Notifications\Repositories;

use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Models\Notification;
use App\Models\User;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NotificationRepository extends BaseRepository
{
    public function __construct(Notification $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?Notification
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var Notification|null $notification */
        $notification = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $notification;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): Notification
    {
        $notification = $this->findByIdentifier($identifier, $withTrashed);

        if (! $notification) {
            abort(404, 'Notification not found.');
        }

        return $notification;
    }

    /**
     * @return list<string>
     */
    protected function defaultRelations(): array
    {
        return [
            'company:id,uuid,company_name',
            'user:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->with($this->defaultRelations())
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $filters['user_id'] = $user->id;

        return $this->paginateFiltered($filters);
    }

    /**
     * @return Collection<int, Notification>
     */
    public function recentForUser(User $user, int $limit = 8): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->where('channel', 'in_app')
            ->with($this->defaultRelations())
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function unreadCountForUser(User $user): int
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->where('channel', 'in_app')
            ->count();
    }

    public function markAllReadForUser(User $user): int
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->where('channel', 'in_app')
            ->update(['read_at' => now()]);
    }

    /**
     * @return array<string, int>
     */
    public function dashboardStatistics(?int $companyId = null): array
    {
        $base = $this->model->newQuery()
            ->when($companyId, fn (Builder $q) => $q->where('company_id', $companyId));

        return [
            'total' => (clone $base)->count(),
            'unread' => (clone $base)->whereNull('read_at')->where('channel', 'in_app')->count(),
            'sent' => (clone $base)->where('status', NotificationStatus::Sent->value)->count(),
            'failed' => (clone $base)->where('status', NotificationStatus::Failed->value)->count(),
            'queued' => (clone $base)->where('status', NotificationStatus::Queued->value)->count(),
            'scheduled' => (clone $base)->where('status', NotificationStatus::Scheduled->value)->count(),
            'by_channel' => (clone $base)
                ->select('channel', DB::raw('count(*) as total'))
                ->groupBy('channel')
                ->pluck('total', 'channel')
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->latest('id');

        if (! blank($filters['user_id'] ?? null)) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! blank($filters['channel'] ?? null)) {
            $query->where('channel', $filters['channel']);
        }

        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (! blank($filters['priority'] ?? null)) {
            $query->where('priority', $filters['priority']);
        }

        if (! blank($filters['event_key'] ?? null)) {
            $query->where('event_key', $filters['event_key']);
        }

        if (($filters['unread'] ?? null) === '1' || ($filters['unread'] ?? null) === true || ($filters['unread'] ?? null) === 1) {
            $query->whereNull('read_at');
        }

        if (($filters['read'] ?? null) === '1' || ($filters['read'] ?? null) === true || ($filters['read'] ?? null) === 1) {
            $query->whereNotNull('read_at');
        }

        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('template', 'like', "%{$search}%")
                    ->orWhere('event_key', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
