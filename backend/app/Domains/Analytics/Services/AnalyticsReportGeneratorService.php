<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\AnalyticsReportType;
use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Domains\Analytics\Models\AnalyticsReport;
use App\Domains\Analytics\Repositories\AnalyticsEventRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnalyticsReportGeneratorService
{
    public function __construct(
        private readonly AnalyticsEventRepository $eventRepository,
    ) {}

    /**
     * Build report dataset for preview / export.
     *
     * @param  array<string, mixed>  $runtimeFilters
     * @return array{
     *     columns: list<array{key: string, label: string}>,
     *     rows: list<array<string, mixed>>,
     *     groups: list<array<string, mixed>>,
     *     chart: array<string, mixed>|null,
     *     meta: array<string, mixed>
     * }
     */
    public function generate(AnalyticsReport $report, array $runtimeFilters = []): array
    {
        $filters = $this->mergeFilters($report, $runtimeFilters);
        $columns = $this->resolveColumns($report);
        $type = $report->report_type instanceof AnalyticsReportType
            ? $report->report_type
            : AnalyticsReportType::tryFrom((string) $report->report_type);

        $rows = $this->buildRows($filters, $columns, $report);
        $groups = [];
        $chart = null;

        if ($type === AnalyticsReportType::Grouped || ! empty($report->grouping)) {
            $groups = $this->buildGroups($rows, $report);
        }

        if ($type === AnalyticsReportType::Chart || ! empty($report->chart_config)) {
            $chart = $this->buildChart($filters, $report);
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'groups' => $groups,
            'chart' => $chart,
            'meta' => [
                'report_uuid' => $report->uuid,
                'report_name' => $report->name,
                'report_type' => $type?->value ?? $report->report_type,
                'row_count' => count($rows),
                'generated_at' => now()->toIso8601String(),
                'filters' => $filters,
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, type?: string}>
     */
    public function availableColumns(): array
    {
        return [
            ['key' => 'occurred_at', 'label' => 'Occurred At', 'type' => 'datetime'],
            ['key' => 'category', 'label' => 'Category', 'type' => 'string'],
            ['key' => 'event_name', 'label' => 'Event Name', 'type' => 'string'],
            ['key' => 'event_source', 'label' => 'Event Source', 'type' => 'string'],
            ['key' => 'subject_type', 'label' => 'Subject Type', 'type' => 'string'],
            ['key' => 'subject_id', 'label' => 'Subject ID', 'type' => 'string'],
            ['key' => 'company_id', 'label' => 'Company ID', 'type' => 'number'],
            ['key' => 'user_id', 'label' => 'User ID', 'type' => 'number'],
            ['key' => 'application_id', 'label' => 'Application ID', 'type' => 'number'],
            ['key' => 'customer_id', 'label' => 'Customer ID', 'type' => 'number'],
            ['key' => 'metric_count', 'label' => 'Metric Count', 'type' => 'number'],
            ['key' => 'metric_value', 'label' => 'Metric Value', 'type' => 'number'],
        ];
    }

    /**
     * @param  array<string, mixed>  $runtimeFilters
     * @return array<string, mixed>
     */
    protected function mergeFilters(AnalyticsReport $report, array $runtimeFilters): array
    {
        $base = is_array($report->filters) ? $report->filters : [];
        $query = is_array($report->query_config) ? $report->query_config : [];

        $merged = array_merge($query, $base, $runtimeFilters);

        if (empty($merged['from'])) {
            $merged['from'] = now()->subDays(29)->toDateString();
        }

        if (empty($merged['to'])) {
            $merged['to'] = now()->toDateString();
        }

        if (! empty($report->category) && empty($merged['category'])) {
            $merged['category'] = $report->category instanceof \BackedEnum
                ? $report->category->value
                : $report->category;
        }

        return $merged;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    protected function resolveColumns(AnalyticsReport $report): array
    {
        $configured = is_array($report->columns) ? $report->columns : [];

        if ($configured !== []) {
            return collect($configured)->map(function ($column): array {
                if (is_string($column)) {
                    return ['key' => $column, 'label' => Str::title(str_replace('_', ' ', $column))];
                }

                return [
                    'key' => (string) ($column['key'] ?? 'value'),
                    'label' => (string) ($column['label'] ?? Str::title(str_replace('_', ' ', (string) ($column['key'] ?? 'value')))),
                ];
            })->values()->all();
        }

        return [
            ['key' => 'occurred_at', 'label' => 'Occurred At'],
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'event_name', 'label' => 'Event Name'],
            ['key' => 'event_source', 'label' => 'Source'],
            ['key' => 'metric_count', 'label' => 'Count'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<array{key: string, label: string}>  $columns
     * @return list<array<string, mixed>>
     */
    protected function buildRows(array $filters, array $columns, AnalyticsReport $report): array
    {
        $events = $this->eventRepository->filteredQuery($filters)
            ->limit(5000)
            ->get();

        $sorting = is_array($report->sorting) ? $report->sorting : [];
        $sortBy = (string) ($sorting['field'] ?? $filters['sort_by'] ?? 'occurred_at');
        $sortDir = strtolower((string) ($sorting['direction'] ?? $filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $mapped = $events->map(function (AnalyticsEvent $event) use ($columns): array {
            $metrics = is_array($event->metrics) ? $event->metrics : [];
            $row = [
                'occurred_at' => optional($event->occurred_at)?->toDateTimeString(),
                'category' => $event->category?->value ?? $event->category,
                'event_name' => $event->event_name,
                'event_source' => $event->event_source,
                'subject_type' => $event->subject_type,
                'subject_id' => $event->subject_id,
                'company_id' => $event->company_id,
                'user_id' => $event->user_id,
                'application_id' => $event->application_id,
                'customer_id' => $event->customer_id,
                'metric_count' => $metrics['count'] ?? 1,
                'metric_value' => $metrics['value'] ?? null,
            ];

            $output = [];
            foreach ($columns as $column) {
                $key = $column['key'];
                $output[$key] = $row[$key] ?? null;
            }

            return $output;
        });

        $sorted = $mapped->sortBy(
            fn (array $row) => $row[$sortBy] ?? null,
            SORT_REGULAR,
            $sortDir === 'desc'
        )->values();

        return $sorted->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function buildGroups(array $rows, AnalyticsReport $report): array
    {
        $grouping = is_array($report->grouping) ? $report->grouping : [];
        $fields = $grouping['fields'] ?? $grouping['by'] ?? ['category'];
        if (is_string($fields)) {
            $fields = [$fields];
        }

        $field = (string) ($fields[0] ?? 'category');
        $aggregate = (string) ($grouping['aggregate'] ?? 'count');

        return collect($rows)
            ->groupBy(fn (array $row) => (string) ($row[$field] ?? 'unknown'))
            ->map(function (Collection $group, string $key) use ($aggregate, $field): array {
                $values = $group->pluck('metric_value')->filter(fn ($v) => is_numeric($v));

                return [
                    'group_key' => $key,
                    'group_field' => $field,
                    'count' => $group->count(),
                    'sum' => round((float) $values->sum(), 2),
                    'avg' => round((float) ($values->avg() ?: 0), 2),
                    'aggregate' => $aggregate,
                    'aggregate_value' => match ($aggregate) {
                        'sum' => round((float) $values->sum(), 2),
                        'avg' => round((float) ($values->avg() ?: 0), 2),
                        default => $group->count(),
                    },
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function buildChart(array $filters, AnalyticsReport $report): array
    {
        $config = is_array($report->chart_config) ? $report->chart_config : [];
        $chartType = (string) ($config['type'] ?? 'bar');

        $byCategory = $this->eventRepository->countByCategory($filters);
        $trend = $this->eventRepository->dailyTrend($filters);
        $topEvents = $this->eventRepository->topEventNames($filters);

        return [
            'type' => $chartType,
            'by_category' => $byCategory,
            'trend' => $trend,
            'top_events' => $topEvents,
            'labels' => array_keys($byCategory),
            'values' => array_values($byCategory),
        ];
    }
}
