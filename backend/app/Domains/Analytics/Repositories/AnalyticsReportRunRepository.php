<?php

namespace App\Domains\Analytics\Repositories;

use App\Domains\Analytics\Models\AnalyticsReportRun;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AnalyticsReportRunRepository extends BaseRepository
{
    public function __construct(AnalyticsReportRun $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): AnalyticsReportRun
    {
        /** @var AnalyticsReportRun $run */
        $run = $this->model->newQuery()->where('uuid', $uuid)->firstOrFail();

        return $run;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForReport(int $reportId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        $query = $this->model->newQuery()
            ->where('analytics_report_id', $reportId)
            ->with(['creator:id,uuid,full_name,email'])
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['format'])) {
            $query->where('format', $filters['format']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
