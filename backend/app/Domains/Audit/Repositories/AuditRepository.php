<?php

namespace App\Domains\Audit\Repositories;

use App\Domains\Audit\Models\AuditLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AuditRepository extends BaseRepository
{
    public function __construct(AuditLog $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?AuditLog
    {
        /** @var AuditLog|null $log */
        $log = $this->model->newQuery()
            ->with(['user:id,uuid,full_name,email', 'company:id,uuid,company_name'])
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })->first();

        return $log;
    }

    public function findByIdentifierOrFail(string $identifier): AuditLog
    {
        $log = $this->findByIdentifier($identifier);
        if (! $log) {
            abort(404, 'Audit log not found.');
        }

        return $log;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));
        $query = $this->model->newQuery()
            ->with(['user:id,uuid,full_name,email', 'company:id,uuid,company_name']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('module', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
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
