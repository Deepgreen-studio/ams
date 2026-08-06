<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\AnalyticsWidgetType;
use App\Domains\Analytics\Events\AnalyticsWidgetCreated;
use App\Domains\Analytics\Events\AnalyticsWidgetDeleted;
use App\Domains\Analytics\Events\AnalyticsWidgetUpdated;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Domains\Analytics\Models\AnalyticsWidget;
use App\Domains\Analytics\Repositories\AnalyticsEventRepository;
use App\Domains\Analytics\Repositories\AnalyticsWidgetRepository;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsWidgetService
{
    public function __construct(
        private readonly AnalyticsWidgetRepository $widgetRepository,
        private readonly AnalyticsEventRepository $eventRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(AnalyticsDashboard $dashboard, array $data, User $actor): AnalyticsWidget
    {
        return DB::transaction(function () use ($dashboard, $data, $actor): AnalyticsWidget {
            $name = trim((string) $data['name']);

            /** @var AnalyticsWidget $widget */
            $widget = $this->widgetRepository->create([
                'analytics_dashboard_id' => $dashboard->id,
                'name' => $name,
                'key' => $this->widgetRepository->uniqueKey($dashboard->id, $data['key'] ?? $name),
                'type' => $data['type'],
                'category' => $data['category'] ?? $dashboard->category?->value ?? $dashboard->category,
                'data_source' => $data['data_source'] ?? $this->defaultDataSource((string) $data['type']),
                'query_config' => $data['query_config'] ?? ['metric' => 'event_count'],
                'visualization_config' => $data['visualization_config'] ?? [],
                'position_x' => (int) ($data['position_x'] ?? 0),
                'position_y' => (int) ($data['position_y'] ?? 0),
                'width' => (int) ($data['width'] ?? $this->defaultWidth((string) $data['type'])),
                'height' => (int) ($data['height'] ?? $this->defaultHeight((string) $data['type'])),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'refresh_interval_seconds' => $data['refresh_interval_seconds'] ?? 300,
                'is_visible' => array_key_exists('is_visible', $data) ? (bool) $data['is_visible'] : true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            event(new AnalyticsWidgetCreated($widget, $actor));

            return $widget->load('dashboard');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AnalyticsWidget $widget, array $data, User $actor): AnalyticsWidget
    {
        return DB::transaction(function () use ($widget, $data, $actor): AnalyticsWidget {
            $name = array_key_exists('name', $data) ? trim((string) $data['name']) : $widget->name;

            $payload = [
                'name' => $name,
                'type' => $data['type'] ?? $widget->type?->value ?? $widget->type,
                'category' => $data['category'] ?? $widget->category?->value ?? $widget->category,
                'data_source' => $data['data_source'] ?? $widget->data_source,
                'query_config' => $data['query_config'] ?? $widget->query_config,
                'visualization_config' => $data['visualization_config'] ?? $widget->visualization_config,
                'position_x' => array_key_exists('position_x', $data) ? (int) $data['position_x'] : $widget->position_x,
                'position_y' => array_key_exists('position_y', $data) ? (int) $data['position_y'] : $widget->position_y,
                'width' => array_key_exists('width', $data) ? (int) $data['width'] : $widget->width,
                'height' => array_key_exists('height', $data) ? (int) $data['height'] : $widget->height,
                'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $widget->sort_order,
                'refresh_interval_seconds' => array_key_exists('refresh_interval_seconds', $data)
                    ? $data['refresh_interval_seconds']
                    : $widget->refresh_interval_seconds,
                'is_visible' => array_key_exists('is_visible', $data)
                    ? (bool) $data['is_visible']
                    : $widget->is_visible,
                'updated_by' => $actor->id,
            ];

            if (array_key_exists('key', $data) && filled($data['key'])) {
                $payload['key'] = $this->widgetRepository->uniqueKey(
                    $widget->analytics_dashboard_id,
                    (string) $data['key'],
                    $widget->id
                );
            } elseif ($name !== $widget->name) {
                $payload['key'] = $this->widgetRepository->uniqueKey(
                    $widget->analytics_dashboard_id,
                    $name,
                    $widget->id
                );
            }

            $widget->update($payload);
            $widget->refresh();

            event(new AnalyticsWidgetUpdated($widget, $actor));

            return $widget->load('dashboard');
        });
    }

    public function delete(AnalyticsWidget $widget, User $actor): void
    {
        DB::transaction(function () use ($widget, $actor): void {
            $widget->update(['updated_by' => $actor->id]);
            $widget->delete();

            event(new AnalyticsWidgetDeleted($widget, $actor));
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function resolveWidgetData(AnalyticsWidget $widget, array $filters = []): array
    {
        $queryConfig = is_array($widget->query_config) ? $widget->query_config : [];
        $category = $queryConfig['category']
            ?? $widget->category?->value
            ?? $widget->category
            ?? null;

        $eventFilters = array_filter([
            'company_id' => $filters['company_id'] ?? null,
            'company' => $filters['company'] ?? null,
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'category' => $category,
            'event_name' => $queryConfig['event_name'] ?? null,
            'event_source' => $queryConfig['event_source'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if (! empty($eventFilters['company']) && empty($eventFilters['company_id'])) {
            // leave company for repository via event service-style resolution later if needed
        }

        $type = $widget->type instanceof AnalyticsWidgetType
            ? $widget->type
            : AnalyticsWidgetType::tryFrom((string) $widget->type);

        $byCategory = $this->eventRepository->countByCategory($eventFilters);
        $trend = $this->eventRepository->dailyTrend($eventFilters);
        $topEvents = $this->eventRepository->topEventNames($eventFilters);

        $data = match ($type) {
            AnalyticsWidgetType::Kpi, AnalyticsWidgetType::Gauge => [
                'value' => array_sum($byCategory),
                'by_category' => $byCategory,
            ],
            AnalyticsWidgetType::LineChart => [
                'trend' => $trend,
                'labels' => array_column($trend, 'date'),
                'values' => array_column($trend, 'count'),
            ],
            AnalyticsWidgetType::BarChart, AnalyticsWidgetType::PieChart => [
                'series' => $topEvents,
                'by_category' => $byCategory,
            ],
            AnalyticsWidgetType::Table => [
                'rows' => collect($this->eventRepository->topEventNames($eventFilters, 20))
                    ->map(fn ($count, $name): array => [
                        'event_name' => $name,
                        'count' => $count,
                    ])
                    ->values()
                    ->all(),
            ],
            AnalyticsWidgetType::Heatmap => [
                'matrix' => $this->buildHeatmapMatrix($trend, $byCategory),
                'by_category' => $byCategory,
                'trend' => $trend,
            ],
            AnalyticsWidgetType::Map => [
                'regions' => $this->buildMapRegions($byCategory, $topEvents),
            ],
            AnalyticsWidgetType::ActivityFeed => [
                'items' => $this->activityFeedItems((int) ($queryConfig['limit'] ?? 15)),
            ],
            AnalyticsWidgetType::Notifications => [
                'items' => $this->notificationFeedItems((int) ($queryConfig['limit'] ?? 15), $eventFilters),
            ],
            default => [
                'by_category' => $byCategory,
                'trend' => $trend,
            ],
        };

        return [
            'uuid' => $widget->uuid,
            'name' => $widget->name,
            'key' => $widget->key,
            'type' => $type?->value ?? $widget->type,
            'category' => $widget->category?->value ?? $widget->category,
            'data_source' => $widget->data_source,
            'position' => [
                'x' => $widget->position_x,
                'y' => $widget->position_y,
                'width' => $widget->width,
                'height' => $widget->height,
            ],
            'visualization_config' => $widget->visualization_config,
            'query_config' => $widget->query_config,
            'is_visible' => (bool) $widget->is_visible,
            'data' => $data,
        ];
    }

    protected function defaultDataSource(string $type): string
    {
        return match ($type) {
            AnalyticsWidgetType::ActivityFeed->value => 'activity_log',
            AnalyticsWidgetType::Notifications->value => 'notifications',
            default => 'analytics_events',
        };
    }

    protected function defaultWidth(string $type): int
    {
        $enum = AnalyticsWidgetType::tryFrom($type);

        return $enum?->defaultWidth() ?? 4;
    }

    protected function defaultHeight(string $type): int
    {
        $enum = AnalyticsWidgetType::tryFrom($type);

        return $enum?->defaultHeight() ?? 2;
    }

    /**
     * @param  list<array{date: string, count: int}>  $trend
     * @param  array<string, int>  $byCategory
     * @return list<array{date: string, category: string, value: int}>
     */
    protected function buildHeatmapMatrix(array $trend, array $byCategory): array
    {
        $categories = array_keys(array_filter($byCategory));
        if ($categories === []) {
            $categories = ['business'];
        }

        $matrix = [];
        foreach (array_slice($trend, -14) as $point) {
            foreach ($categories as $category) {
                $matrix[] = [
                    'date' => $point['date'],
                    'category' => $category,
                    'value' => (int) round(($point['count'] * (($byCategory[$category] ?? 1) / max(1, array_sum($byCategory))))),
                ];
            }
        }

        return $matrix;
    }

    /**
     * @param  array<string, int>  $byCategory
     * @param  array<string, int>  $topEvents
     * @return list<array{code: string, label: string, value: int}>
     */
    protected function buildMapRegions(array $byCategory, array $topEvents): array
    {
        $seedRegions = [
            ['code' => 'NA', 'label' => 'North America'],
            ['code' => 'EU', 'label' => 'Europe'],
            ['code' => 'APAC', 'label' => 'Asia Pacific'],
            ['code' => 'LATAM', 'label' => 'Latin America'],
            ['code' => 'MEA', 'label' => 'Middle East & Africa'],
            ['code' => 'OTHER', 'label' => 'Other'],
        ];

        $values = array_values($byCategory);
        if ($values === []) {
            $values = array_values($topEvents);
        }

        return collect($seedRegions)->map(function (array $region, int $index) use ($values): array {
            return [
                'code' => $region['code'],
                'label' => $region['label'],
                'value' => (int) ($values[$index % max(1, count($values))] ?? 0),
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function activityFeedItems(int $limit): array
    {
        if (! class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            return [];
        }

        return \Spatie\Activitylog\Models\Activity::query()
            ->latest()
            ->limit(max(1, min($limit, 50)))
            ->get(['id', 'log_name', 'description', 'event', 'causer_id', 'created_at'])
            ->map(fn ($activity): array => [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'event' => $activity->event,
                'causer_id' => $activity->causer_id,
                'created_at' => $activity->created_at,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    protected function notificationFeedItems(int $limit, array $filters): array
    {
        $filters['event_source'] = $filters['event_source'] ?? 'notifications';
        $filters['category'] = $filters['category'] ?? 'operational';

        $events = $this->eventRepository->paginateFiltered(array_merge($filters, [
            'per_page' => max(1, min($limit, 50)),
            'sort_by' => 'occurred_at',
            'sort_dir' => 'desc',
        ]));

        return collect($events->items())->map(fn ($event): array => [
            'uuid' => $event->uuid,
            'event_name' => $event->event_name,
            'event_source' => $event->event_source,
            'category' => $event->category?->value ?? $event->category,
            'occurred_at' => $event->occurred_at,
            'metrics' => $event->metrics,
        ])->all();
    }
}
