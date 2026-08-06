<?php

namespace App\Domains\Audit\Repositories;

use App\Domains\Audit\Models\ApiLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ApiLogRepository extends BaseRepository
{
    public function __construct(ApiLog $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifierOrFail(string $identifier): ApiLog
    {
        /** @var ApiLog|null $log */
        $log = $this->model->newQuery()
            ->with(['user:id,uuid,full_name,email'])
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })->first();

        if (! $log) {
            abort(404, 'API log not found.');
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
            $query->where('endpoint', 'like', "%{$search}%");
        }

        if (! empty($filters['method'])) {
            $query->where('method', strtoupper((string) $filters['method']));
        }

        if (! empty($filters['response_code'])) {
            $query->where('response_code', (int) $filters['response_code']);
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
