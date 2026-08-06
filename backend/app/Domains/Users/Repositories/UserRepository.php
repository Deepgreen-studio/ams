<?php

namespace App\Domains\Users\Repositories;

use App\Domains\Users\Models\UserLoginHistory;
use App\Models\User;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->where('email', $email)
            ->first();

        return $user;
    }

    public function findByPhone(string $phone): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->where('phone', $phone)
            ->first();

        return $user;
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?User
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var User|null $user */
        $user = $query
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);

                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        return $user;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): User
    {
        $user = $this->findByIdentifier($identifier, $withTrashed);

        if (! $user) {
            abort(404, 'User not found.');
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));

        $query = $this->filteredQuery($filters)
            ->with(['creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['trashed']) && $filters['trashed'] === 'only') {
            $query->onlyTrashed();
        } elseif (! empty($filters['trashed']) && $filters['trashed'] === 'with') {
            $query->withTrashed();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('full_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $statuses = is_array($filters['status'])
                ? $filters['status']
                : explode(',', (string) $filters['status']);

            $query->whereIn('status', array_filter($statuses));
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (! empty($filters['updated_by'])) {
            $query->where('updated_by', $filters['updated_by']);
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = [
            'id',
            'full_name',
            'email',
            'phone',
            'status',
            'created_at',
            'updated_at',
            'last_login_at',
        ];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): User
    {
        /** @var User $user */
        $user = $this->model->newQuery()->create($data);

        return $user->fresh(['creator', 'updater']) ?? $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateUser(User $user, array $data): User
    {
        $user->fill($data);
        $user->save();

        return $user->refresh()->load(['creator', 'updater']);
    }

    public function softDeleteUser(User $user): bool
    {
        return (bool) $user->delete();
    }

    public function restoreUser(User $user): User
    {
        $user->restore();

        return $user->refresh()->load(['creator', 'updater']);
    }

    public function forceDeleteUser(User $user): bool
    {
        return (bool) $user->forceDelete();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        $base = $this->model->newQuery();

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
            'suspended' => (clone $base)->where('status', 'suspended')->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'trashed' => (clone $base)->onlyTrashed()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function activitySummary(User $user): array
    {
        $activityModel = config('activitylog.activity_model');

        $query = $activityModel::query()->forSubject($user);

        $activities = (clone $query)
            ->latest()
            ->limit(50)
            ->get();

        return [
            'total' => (clone $query)->count(),
            'recent' => $activities->take(10)->map(static function ($activity): array {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'log_name' => $activity->log_name,
                    'created_at' => $activity->created_at,
                    'causer_id' => $activity->causer_id,
                    'properties' => $activity->properties,
                ];
            })->values(),
            'last_activity_at' => $activities->first()?->created_at,
        ];
    }

    /**
     * Architecture-ready login history query.
     *
     * @return LengthAwarePaginator<int, UserLoginHistory>
     */
    public function paginateLoginHistory(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return UserLoginHistory::query()
            ->where('user_id', $user->id)
            ->latest('logged_in_at')
            ->paginate($perPage);
    }

    /**
     * @param  list<int|string>  $ids
     * @return Collection<int, User>
     */
    public function findManyByIdentifiers(array $ids): Collection
    {
        return $this->model->newQuery()
            ->whereIn('uuid', $ids)
            ->orWhereIn('id', array_filter($ids, 'ctype_digit'))
            ->get();
    }
}
