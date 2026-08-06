<?php

namespace App\Domains\Integrations\Repositories;

use App\Domains\Integrations\Models\SyncRun;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SyncRunRepository extends BaseRepository
{
    public function __construct(SyncRun $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): SyncRun
    {
        /** @var SyncRun|null $run */
        $run = $this->model->newQuery()->where('uuid', $uuid)->first();
        if (! $run) {
            abort(404, 'Sync run not found.');
        }

        return $run;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->filteredQuery($filters)
            ->with([
                'config:id,uuid,name,slug',
                'integration:id,uuid,name,slug',
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

        if (! empty($filters['sync_config_id'])) {
            $query->where('sync_config_id', (int) $filters['sync_config_id']);
        }
        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['trigger'])) {
            $query->where('trigger', $filters['trigger']);
        }
        if (! empty($filters['mode'])) {
            $query->where('mode', $filters['mode']);
        }

        return $query->orderByDesc('created_at');
    }

    /** @param  array<string, mixed>  $data */
    public function createRun(array $data): SyncRun
    {
        /** @var SyncRun $run */
        $run = $this->model->newQuery()->create($data);

        return $run;
    }

    /** @param  array<string, mixed>  $data */
    public function updateRun(SyncRun $run, array $data): SyncRun
    {
        $run->fill($data);
        $run->save();

        return $run->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardStats(?int $companyId = null): array
    {
        $query = $this->model->newQuery();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $totals = (clone $query)->selectRaw('
            COUNT(*) as total_runs,
            SUM(CASE WHEN status = "running" THEN 1 ELSE 0 END) as running,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
            SUM(total_records) as total_records,
            SUM(imported) as imported,
            SUM(updated) as updated,
            SUM(failed) as failed_records,
            SUM(skipped) as skipped,
            SUM(exported) as exported
        ')->first();

        $recent = (clone $query)->with(['config:id,uuid,name', 'integration:id,uuid,name'])
            ->latest()
            ->limit(8)
            ->get();

        return [
            'totals' => [
                'total_runs' => (int) ($totals->total_runs ?? 0),
                'running' => (int) ($totals->running ?? 0),
                'completed' => (int) ($totals->completed ?? 0),
                'failed' => (int) ($totals->failed ?? 0),
                'total_records' => (int) ($totals->total_records ?? 0),
                'imported' => (int) ($totals->imported ?? 0),
                'updated' => (int) ($totals->updated ?? 0),
                'failed_records' => (int) ($totals->failed_records ?? 0),
                'skipped' => (int) ($totals->skipped ?? 0),
                'exported' => (int) ($totals->exported ?? 0),
            ],
            'recent_runs' => $recent,
        ];
    }
}
