<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Models\IntegrationConnectionLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class IntegrationConnectionLogRepository extends BaseRepository
{
    public function __construct(IntegrationConnectionLog $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): IntegrationConnectionLog
    {
        /** @var IntegrationConnectionLog|null $log */
        $log = $this->model->newQuery()->where('uuid', $uuid)->first();

        if (! $log) {
            abort(404, 'Connection history entry not found.');
        }

        return $log;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForIntegration(int $integrationId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($integrationId, $filters)
            ->with(['actor:id,uuid,full_name,email'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(int $integrationId, array $filters = []): Builder
    {
        $query = $this->model->newQuery()->where('integration_id', $integrationId);

        if (! empty($filters['request_type'])) {
            $query->where('request_type', $filters['request_type']);
        }

        if (! empty($filters['method'])) {
            $query->where('method', strtoupper((string) $filters['method']));
        }

        if (array_key_exists('success', $filters) && $filters['success'] !== '' && $filters['success'] !== null) {
            $query->where('success', filter_var($filters['success'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('url', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'duration_ms', 'response_status', 'method', 'success'];

        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createLog(array $data): IntegrationConnectionLog
    {
        /** @var IntegrationConnectionLog $log */
        $log = $this->model->newQuery()->create($data);

        return $log->fresh(['actor']) ?? $log;
    }
}
