<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Models\WebhookLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WebhookLogRepository extends BaseRepository
{
    public function __construct(WebhookLog $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): WebhookLog
    {
        /** @var WebhookLog|null $log */
        $log = $this->model->newQuery()->where('uuid', $uuid)->first();
        if (! $log) {
            abort(404, 'Webhook log not found.');
        }

        return $log;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'webhook:id,uuid,name,slug,direction,status',
                'event:id,uuid,name,label',
                'actor:id,uuid,full_name,email',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['webhook_id'])) {
            $query->where('webhook_id', (int) $filters['webhook_id']);
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (! empty($filters['event_name'])) {
            $query->where('event_name', $filters['event_name']);
        }

        if (array_key_exists('is_test', $filters) && $filters['is_test'] !== '' && $filters['is_test'] !== null) {
            $query->where('is_test', filter_var($filters['is_test'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('url', 'like', "%{$search}%")
                    ->orWhere('event_name', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowed = ['created_at', 'status', 'response_status', 'duration_ms', 'attempts'];
        if (! in_array($sortBy, $allowed, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createLog(array $data): WebhookLog
    {
        /** @var WebhookLog $log */
        $log = $this->model->newQuery()->create($data);

        return $log->fresh(['webhook', 'event', 'actor']) ?? $log;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateLog(WebhookLog $log, array $data): WebhookLog
    {
        $log->fill($data);
        $log->save();

        return $log->refresh()->load(['webhook', 'event', 'actor']);
    }
}
