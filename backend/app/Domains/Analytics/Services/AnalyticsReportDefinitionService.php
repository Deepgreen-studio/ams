<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\AnalyticsReportStatus;
use App\Domains\Analytics\Enums\AnalyticsReportType;
use App\Domains\Analytics\Enums\AnalyticsReportVisibility;
use App\Domains\Analytics\Models\AnalyticsReport;
use App\Domains\Analytics\Repositories\AnalyticsReportRepository;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AnalyticsReportDefinitionService
{
    public function __construct(
        private readonly AnalyticsReportRepository $reportRepository,
        private readonly AnalyticsReportScheduleService $scheduleService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->reportRepository->paginateFiltered($this->normalizeFilters($filters));
    }

    public function find(string $uuid): AnalyticsReport
    {
        return $this->reportRepository->findByUuidOrFail($uuid)
            ->load([
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
                'owner:id,uuid,full_name,email',
                'scheduledJob:id,uuid,name,handler_key,schedule_cron,is_enabled,next_run_at,payload',
            ])
            ->loadCount('runs');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): AnalyticsReport
    {
        return DB::transaction(function () use ($data, $actor): AnalyticsReport {
            $companyId = $this->resolveCompanyId($data['company_id'] ?? $data['company'] ?? null);
            $name = trim((string) $data['name']);

            /** @var AnalyticsReport $report */
            $report = $this->reportRepository->create($this->mapPayload($data, $actor, $companyId, $name, isCreate: true));

            if (! empty($data['schedule_config']['enabled'])) {
                $report = $this->scheduleService->sync($report, $data['schedule_config'], $actor);
            }

            return $this->find($report->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AnalyticsReport $report, array $data, User $actor): AnalyticsReport
    {
        return DB::transaction(function () use ($report, $data, $actor): AnalyticsReport {
            $companyId = array_key_exists('company_id', $data) || array_key_exists('company', $data)
                ? $this->resolveCompanyId($data['company_id'] ?? $data['company'] ?? null)
                : $report->company_id;

            $name = array_key_exists('name', $data) ? trim((string) $data['name']) : $report->name;
            $payload = $this->mapPayload($data, $actor, $companyId, $name, isCreate: false, report: $report);

            if ($name !== $report->name) {
                $payload['slug'] = $this->reportRepository->uniqueSlug($name, $companyId, $report->id);
            }

            $report->update($payload);
            $report = $report->fresh();

            if (array_key_exists('schedule_config', $data)) {
                $report = $this->scheduleService->sync($report, is_array($data['schedule_config']) ? $data['schedule_config'] : [], $actor);
            }

            return $this->find($report->uuid);
        });
    }

    /**
     * Persist designer fields without requiring a full update payload.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveDesigner(AnalyticsReport $report, array $data, User $actor): AnalyticsReport
    {
        return $this->update($report, array_merge($data, [
            'status' => $data['status'] ?? AnalyticsReportStatus::Active->value,
            'is_saved' => $data['is_saved'] ?? true,
        ]), $actor);
    }

    public function delete(AnalyticsReport $report, User $actor): void
    {
        DB::transaction(function () use ($report, $actor): void {
            $this->scheduleService->disable($report, $actor);
            $report->update(['updated_by' => $actor->id]);
            $report->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mapPayload(
        array $data,
        User $actor,
        ?int $companyId,
        string $name,
        bool $isCreate,
        ?AnalyticsReport $report = null
    ): array {
        $type = $data['report_type']
            ?? ($report?->report_type instanceof AnalyticsReportType
                ? $report->report_type->value
                : ($report?->report_type ?? AnalyticsReportType::Tabular->value));

        $payload = [
            'company_id' => $companyId,
            'owner_id' => $data['owner_id'] ?? ($isCreate ? $actor->id : ($report?->owner_id ?? $actor->id)),
            'name' => $name,
            'description' => $data['description'] ?? ($report?->description),
            'category' => $data['category'] ?? ($report?->category?->value ?? $report?->category ?? 'business'),
            'report_type' => $type,
            'status' => $data['status'] ?? ($report?->status?->value ?? $report?->status ?? AnalyticsReportStatus::Draft->value),
            'visibility' => $data['visibility'] ?? ($report?->visibility?->value ?? $report?->visibility ?? AnalyticsReportVisibility::Personal->value),
            'is_saved' => array_key_exists('is_saved', $data)
                ? (bool) $data['is_saved']
                : ($isCreate ? true : (bool) $report?->is_saved),
            'query_config' => $data['query_config'] ?? ($report?->query_config ?? []),
            'columns' => $data['columns'] ?? ($report?->columns),
            'filters' => $data['filters'] ?? ($report?->filters),
            'sorting' => $data['sorting'] ?? ($report?->sorting),
            'grouping' => $data['grouping'] ?? ($report?->grouping),
            'chart_config' => $data['chart_config'] ?? ($report?->chart_config),
            'layout' => $data['layout'] ?? ($report?->layout),
            'format_defaults' => $data['format_defaults'] ?? ($report?->format_defaults ?? ['format' => 'csv']),
            'updated_by' => $actor->id,
        ];

        if ($isCreate) {
            $payload['slug'] = $this->reportRepository->uniqueSlug($name, $companyId);
            $payload['created_by'] = $actor->id;
            $payload['schedule_config'] = $data['schedule_config'] ?? null;
            $payload['is_scheduled'] = false;
        } elseif (array_key_exists('schedule_config', $data)) {
            $payload['schedule_config'] = $data['schedule_config'];
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function normalizeFilters(array $filters): array
    {
        if (! empty($filters['company']) && empty($filters['company_id'])) {
            $filters['company_id'] = $this->resolveCompanyId($filters['company']);
        }

        return $filters;
    }

    protected function resolveCompanyId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return Company::query()->where('uuid', (string) $value)->value('id');
    }
}
