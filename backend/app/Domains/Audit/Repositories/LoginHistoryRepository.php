<?php

namespace App\Domains\Audit\Repositories;

use App\Domains\Users\Models\UserLoginHistory;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LoginHistoryRepository extends BaseRepository
{
    public function __construct(UserLoginHistory $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));
        $query = $this->model->newQuery()->with(['user:id,uuid,full_name,email']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('browser', 'like', "%{$search}%")
                    ->orWhere('device', 'like', "%{$search}%")
                    ->orWhere('operating_system', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('logged_in_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('logged_in_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('logged_in_at')->paginate($perPage)->withQueryString();
    }

    public function findOpenSession(int $userId, ?string $sessionId = null): ?UserLoginHistory
    {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->whereNull('logout_at')
            ->where('status', 'success')
            ->orderByDesc('logged_in_at');

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        /** @var UserLoginHistory|null $history */
        $history = $query->first();

        return $history;
    }
}
