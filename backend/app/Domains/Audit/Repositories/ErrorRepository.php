<?php

namespace App\Domains\Audit\Repositories;

use App\Domains\Audit\Models\ErrorLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ErrorRepository extends BaseRepository
{
    public function __construct(ErrorLog $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifierOrFail(string $identifier): ErrorLog
    {
        /** @var ErrorLog|null $log */
        $log = $this->model->newQuery()
            ->with(['user:id,uuid,full_name,email'])
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })->first();

        if (! $log) {
            abort(404, 'Error log not found.');
        }

        return $log;
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
                $builder->where('message', 'like', "%{$search}%")
                    ->orWhere('exception', 'like', "%{$search}%")
                    ->orWhere('file', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['exception'])) {
            $query->where('exception', 'like', '%'.$filters['exception'].'%');
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }
}
