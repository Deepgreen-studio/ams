<?php

namespace App\Domains\Scheduler\Repositories;

use App\Domains\Scheduler\Enums\ScheduledJobType;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ScheduledJobRepository extends BaseRepository
{
    public function __construct(ScheduledJob $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier, bool $withTrashed = false): ?ScheduledJob
    {
        $query = $this->model->newQuery();
        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ScheduledJob|null $job */
        $job = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $job;
    }

    public function findByIdentifierOrFail(string $identifier, bool $withTrashed = false): ScheduledJob
    {
        $job = $this->findByIdentifier($identifier, $withTrashed);
        if (! $job) {
            abort(404, 'Scheduled job not found.');
        }

        return $job;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->with(['company:id,uuid,company_name', 'creator:id,uuid,full_name,email'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, ScheduledJob>
     */
    public function dueJobs(int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->where('is_enabled', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->whereIn('job_type', [
                ScheduledJobType::Cron->value,
                ScheduledJobType::Recurring->value,
                ScheduledJobType::OneTime->value,
                ScheduledJobType::Delayed->value,
                ScheduledJobType::Queue->value,
            ])
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'enabled' => $this->model->newQuery()->where('is_enabled', true)->count(),
            'disabled' => $this->model->newQuery()->where('is_enabled', false)->count(),
            'cron' => $this->model->newQuery()->where('job_type', ScheduledJobType::Cron->value)->count(),
            'recurring' => $this->model->newQuery()->where('job_type', ScheduledJobType::Recurring->value)->count(),
            'one_time' => $this->model->newQuery()->where('job_type', ScheduledJobType::OneTime->value)->count(),
            'delayed' => $this->model->newQuery()->where('job_type', ScheduledJobType::Delayed->value)->count(),
            'queue' => $this->model->newQuery()->where('job_type', ScheduledJobType::Queue->value)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->latest('id');

        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! blank($filters['job_type'] ?? null)) {
            $query->where('job_type', $filters['job_type']);
        }
        if (! blank($filters['handler_key'] ?? null)) {
            $query->where('handler_key', $filters['handler_key']);
        }
        if (array_key_exists('is_enabled', $filters) && $filters['is_enabled'] !== null && $filters['is_enabled'] !== '') {
            $query->where('is_enabled', filter_var($filters['is_enabled'], FILTER_VALIDATE_BOOLEAN));
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('handler_key', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
