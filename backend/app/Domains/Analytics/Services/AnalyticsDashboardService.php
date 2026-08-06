<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\AnalyticsDashboardKind;
use App\Domains\Analytics\Enums\AnalyticsDashboardStatus;
use App\Domains\Analytics\Enums\AnalyticsDashboardVisibility;
use App\Domains\Analytics\Events\AnalyticsDashboardCreated;
use App\Domains\Analytics\Events\AnalyticsDashboardDeleted;
use App\Domains\Analytics\Events\AnalyticsDashboardUpdated;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Domains\Analytics\Repositories\AnalyticsDashboardRepository;
use App\Domains\Analytics\Repositories\AnalyticsWidgetRepository;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboardService
{
    public function __construct(
        private readonly AnalyticsDashboardRepository $dashboardRepository,
        private readonly AnalyticsWidgetRepository $widgetRepository,
        private readonly AnalyticsWidgetService $widgetService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], ?User $actor = null): LengthAwarePaginator
    {
        $normalized = $this->normalizeFilters($filters);

        if ($actor && empty($normalized['skip_access_scope'])) {
            $normalized['accessible_by'] = $actor;
        }

        return $this->dashboardRepository->paginateFiltered($normalized);
    }

    public function find(string $uuid): AnalyticsDashboard
    {
        return $this->dashboardRepository->findByUuidOrFail($uuid)
            ->load([
                'widgets',
                'shares',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
                'owner:id,uuid,full_name,email',
                'company:id,uuid,company_name',
            ]);
    }

    /**
     * @return Collection<int, AnalyticsDashboard>
     */
    public function templates(): Collection
    {
        return $this->dashboardRepository->templates();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): AnalyticsDashboard
    {
        return DB::transaction(function () use ($data, $actor): AnalyticsDashboard {
            $companyId = $this->resolveCompanyId($data['company_id'] ?? $data['company'] ?? null);
            $name = trim((string) $data['name']);
            $visibility = $data['visibility'] ?? AnalyticsDashboardVisibility::Personal->value;
            $isTemplate = (bool) ($data['is_template'] ?? ($visibility === AnalyticsDashboardVisibility::Template->value));

            if ($isTemplate) {
                $visibility = AnalyticsDashboardVisibility::Template->value;
            }

            $payload = [
                'company_id' => $companyId,
                'owner_id' => $data['owner_id'] ?? $actor->id,
                'name' => $name,
                'slug' => $this->dashboardRepository->uniqueSlug($name, $companyId),
                'description' => $data['description'] ?? null,
                'kind' => $data['kind'] ?? AnalyticsDashboardKind::Dashboard->value,
                'category' => $data['category'] ?? 'business',
                'status' => $data['status'] ?? AnalyticsDashboardStatus::Draft->value,
                'visibility' => $visibility,
                'layout' => $data['layout'] ?? ['columns' => 12, 'row_height' => 80, 'gap' => 16],
                'filters' => $data['filters'] ?? $this->defaultFilters(),
                'settings' => $data['settings'] ?? $this->defaultSettings(),
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_system' => false,
                'is_shared' => (bool) ($data['is_shared'] ?? false),
                'is_template' => $isTemplate,
                'template_source_id' => $data['template_source_id'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ];

            if ($payload['is_default']) {
                $this->clearDefaultFlags($companyId, null, $actor->id, $visibility);
            }

            /** @var AnalyticsDashboard $dashboard */
            $dashboard = $this->dashboardRepository->create($payload);

            event(new AnalyticsDashboardCreated($dashboard, $actor));

            return $dashboard->load(['widgets', 'creator', 'updater', 'owner']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromTemplate(AnalyticsDashboard $template, array $data, User $actor): AnalyticsDashboard
    {
        if (! $template->is_template && $template->visibility !== AnalyticsDashboardVisibility::Template) {
            abort(422, 'Selected dashboard is not a template.');
        }

        return DB::transaction(function () use ($template, $data, $actor): AnalyticsDashboard {
            $name = trim((string) ($data['name'] ?? $template->name.' Dashboard'));
            $companyId = $this->resolveCompanyId($data['company_id'] ?? $data['company'] ?? $template->company_id);
            $visibility = $data['visibility'] ?? AnalyticsDashboardVisibility::Personal->value;

            /** @var AnalyticsDashboard $dashboard */
            $dashboard = $this->dashboardRepository->create([
                'company_id' => $companyId,
                'owner_id' => $actor->id,
                'name' => $name,
                'slug' => $this->dashboardRepository->uniqueSlug($name, $companyId),
                'description' => $data['description'] ?? $template->description,
                'kind' => AnalyticsDashboardKind::Dashboard->value,
                'category' => $data['category'] ?? $template->category?->value ?? $template->category,
                'status' => AnalyticsDashboardStatus::Draft->value,
                'visibility' => $visibility,
                'layout' => $template->layout,
                'filters' => $data['filters'] ?? $template->filters,
                'settings' => $data['settings'] ?? $template->settings ?? $this->defaultSettings(),
                'is_default' => false,
                'is_system' => false,
                'is_shared' => false,
                'is_template' => false,
                'template_source_id' => $template->id,
                'sort_order' => 0,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($template->widgets as $widget) {
                $this->widgetRepository->create([
                    'analytics_dashboard_id' => $dashboard->id,
                    'name' => $widget->name,
                    'key' => $this->widgetRepository->uniqueKey($dashboard->id, $widget->name),
                    'type' => $widget->type?->value ?? $widget->type,
                    'category' => $widget->category?->value ?? $widget->category,
                    'data_source' => $widget->data_source,
                    'query_config' => $widget->query_config,
                    'visualization_config' => $widget->visualization_config,
                    'position_x' => $widget->position_x,
                    'position_y' => $widget->position_y,
                    'width' => $widget->width,
                    'height' => $widget->height,
                    'sort_order' => $widget->sort_order,
                    'refresh_interval_seconds' => $widget->refresh_interval_seconds,
                    'is_visible' => $widget->is_visible,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            }

            event(new AnalyticsDashboardCreated($dashboard, $actor));

            return $dashboard->load(['widgets', 'creator', 'updater', 'owner']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AnalyticsDashboard $dashboard, array $data, User $actor): AnalyticsDashboard
    {
        return DB::transaction(function () use ($dashboard, $data, $actor): AnalyticsDashboard {
            if ($dashboard->is_system && ! $actor->can('analytics.manage')) {
                abort(403, 'System dashboards can only be updated by analytics managers.');
            }

            $companyId = array_key_exists('company_id', $data) || array_key_exists('company', $data)
                ? $this->resolveCompanyId($data['company_id'] ?? $data['company'] ?? null)
                : $dashboard->company_id;

            $name = array_key_exists('name', $data) ? trim((string) $data['name']) : $dashboard->name;
            $visibility = $data['visibility'] ?? $dashboard->visibility?->value ?? $dashboard->visibility;
            $isTemplate = array_key_exists('is_template', $data)
                ? (bool) $data['is_template']
                : $dashboard->is_template;

            if ($isTemplate) {
                $visibility = AnalyticsDashboardVisibility::Template->value;
            }

            $payload = [
                'company_id' => $companyId,
                'owner_id' => array_key_exists('owner_id', $data) ? $data['owner_id'] : $dashboard->owner_id,
                'name' => $name,
                'description' => $data['description'] ?? $dashboard->description,
                'kind' => $data['kind'] ?? $dashboard->kind?->value ?? $dashboard->kind,
                'category' => $data['category'] ?? $dashboard->category?->value ?? $dashboard->category,
                'status' => $data['status'] ?? $dashboard->status?->value ?? $dashboard->status,
                'visibility' => $visibility,
                'layout' => $data['layout'] ?? $dashboard->layout,
                'filters' => $data['filters'] ?? $dashboard->filters,
                'settings' => $data['settings'] ?? $dashboard->settings,
                'is_default' => array_key_exists('is_default', $data)
                    ? (bool) $data['is_default']
                    : $dashboard->is_default,
                'is_shared' => array_key_exists('is_shared', $data)
                    ? (bool) $data['is_shared']
                    : $dashboard->is_shared,
                'is_template' => $isTemplate,
                'sort_order' => array_key_exists('sort_order', $data)
                    ? (int) $data['sort_order']
                    : $dashboard->sort_order,
                'updated_by' => $actor->id,
            ];

            if ($name !== $dashboard->name) {
                $payload['slug'] = $this->dashboardRepository->uniqueSlug($name, $companyId, $dashboard->id);
            }

            if (! empty($payload['is_default'])) {
                $this->clearDefaultFlags($companyId, $dashboard->id, $dashboard->owner_id, (string) $visibility);
            }

            $dashboard->update($payload);
            $dashboard->refresh();

            event(new AnalyticsDashboardUpdated($dashboard, $actor));

            return $dashboard->load(['widgets', 'creator', 'updater', 'owner', 'shares']);
        });
    }

    /**
     * Persist designer grid layout for all widgets in one request.
     *
     * @param  list<array<string, mixed>>  $widgets
     * @param  array<string, mixed>|null  $layout
     */
    public function saveLayout(AnalyticsDashboard $dashboard, array $widgets, ?array $layout, User $actor): AnalyticsDashboard
    {
        return DB::transaction(function () use ($dashboard, $widgets, $layout, $actor): AnalyticsDashboard {
            if ($dashboard->is_system && ! $actor->can('analytics.manage')) {
                abort(403, 'System dashboards can only be updated by analytics managers.');
            }

            foreach ($widgets as $index => $item) {
                $uuid = (string) ($item['uuid'] ?? '');
                if ($uuid === '') {
                    continue;
                }

                $widget = $this->widgetRepository->findByUuidOrFail($uuid);
                if ($widget->analytics_dashboard_id !== $dashboard->id) {
                    abort(422, 'Widget does not belong to this dashboard.');
                }

                $widget->update([
                    'position_x' => max(0, min(11, (int) ($item['position_x'] ?? $widget->position_x))),
                    'position_y' => max(0, (int) ($item['position_y'] ?? $widget->position_y)),
                    'width' => max(2, min(12, (int) ($item['width'] ?? $widget->width))),
                    'height' => max(2, min(12, (int) ($item['height'] ?? $widget->height))),
                    'sort_order' => (int) ($item['sort_order'] ?? $index),
                    'is_visible' => array_key_exists('is_visible', $item)
                        ? (bool) $item['is_visible']
                        : $widget->is_visible,
                    'updated_by' => $actor->id,
                ]);
            }

            if ($layout !== null) {
                $dashboard->update([
                    'layout' => array_merge(is_array($dashboard->layout) ? $dashboard->layout : [], $layout),
                    'updated_by' => $actor->id,
                ]);
            } else {
                $dashboard->update(['updated_by' => $actor->id]);
            }

            $dashboard->refresh();
            event(new AnalyticsDashboardUpdated($dashboard, $actor));

            return $dashboard->load(['widgets', 'owner', 'creator', 'updater']);
        });
    }

    public function delete(AnalyticsDashboard $dashboard, User $actor): void
    {
        DB::transaction(function () use ($dashboard, $actor): void {
            if ($dashboard->is_system) {
                abort(403, 'System dashboards cannot be deleted.');
            }

            $dashboard->update(['updated_by' => $actor->id]);
            $dashboard->delete();

            event(new AnalyticsDashboardDeleted($dashboard, $actor));
        });
    }

    public function duplicate(AnalyticsDashboard $dashboard, User $actor): AnalyticsDashboard
    {
        return DB::transaction(function () use ($dashboard, $actor): AnalyticsDashboard {
            $name = $dashboard->name.' (Copy)';
            $companyId = $dashboard->company_id;

            /** @var AnalyticsDashboard $copy */
            $copy = $this->dashboardRepository->create([
                'company_id' => $companyId,
                'owner_id' => $actor->id,
                'name' => $name,
                'slug' => $this->dashboardRepository->uniqueSlug($name, $companyId),
                'description' => $dashboard->description,
                'kind' => $dashboard->kind?->value ?? $dashboard->kind,
                'category' => $dashboard->category?->value ?? $dashboard->category,
                'status' => AnalyticsDashboardStatus::Draft->value,
                'visibility' => AnalyticsDashboardVisibility::Personal->value,
                'layout' => $dashboard->layout,
                'filters' => $dashboard->filters,
                'settings' => $dashboard->settings,
                'is_default' => false,
                'is_system' => false,
                'is_shared' => false,
                'is_template' => false,
                'template_source_id' => $dashboard->is_template ? $dashboard->id : $dashboard->template_source_id,
                'sort_order' => $dashboard->sort_order,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($dashboard->widgets as $widget) {
                $this->widgetRepository->create([
                    'analytics_dashboard_id' => $copy->id,
                    'name' => $widget->name,
                    'key' => $this->widgetRepository->uniqueKey($copy->id, $widget->name),
                    'type' => $widget->type?->value ?? $widget->type,
                    'category' => $widget->category?->value ?? $widget->category,
                    'data_source' => $widget->data_source,
                    'query_config' => $widget->query_config,
                    'visualization_config' => $widget->visualization_config,
                    'position_x' => $widget->position_x,
                    'position_y' => $widget->position_y,
                    'width' => $widget->width,
                    'height' => $widget->height,
                    'sort_order' => $widget->sort_order,
                    'refresh_interval_seconds' => $widget->refresh_interval_seconds,
                    'is_visible' => $widget->is_visible,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            }

            event(new AnalyticsDashboardCreated($copy, $actor));

            return $copy->load(['widgets', 'creator', 'updater', 'owner']);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function resolveData(AnalyticsDashboard $dashboard, array $filters = []): array
    {
        $mergedFilters = array_merge(
            is_array($dashboard->filters) ? $dashboard->filters : [],
            $filters
        );

        $widgets = $this->widgetRepository->forDashboard($dashboard->id, true);

        return [
            'dashboard' => $dashboard,
            'filters' => $mergedFilters,
            'widgets' => $widgets->map(
                fn ($widget) => $this->widgetService->resolveWidgetData($widget, $mergedFilters)
            )->values()->all(),
        ];
    }

    /**
     * @return array{from: string, to: string}
     */
    protected function defaultFilters(): array
    {
        return [
            'from' => now()->subDays(29)->toDateString(),
            'to' => now()->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSettings(): array
    {
        return [
            'auto_refresh_seconds' => 300,
            'theme' => 'light',
            'show_filters' => true,
            'dense' => false,
        ];
    }

    protected function clearDefaultFlags(
        ?int $companyId,
        ?int $exceptId = null,
        ?int $ownerId = null,
        ?string $visibility = null
    ): void {
        $query = AnalyticsDashboard::query()->where('is_default', true);

        if ($visibility === AnalyticsDashboardVisibility::Personal->value && $ownerId) {
            $query->where('owner_id', $ownerId)
                ->where('visibility', AnalyticsDashboardVisibility::Personal->value);
        } elseif ($companyId === null) {
            $query->whereNull('company_id');
        } else {
            $query->where('company_id', $companyId);
        }

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_default' => false]);
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

        $company = Company::query()
            ->where('uuid', (string) $value)
            ->first();

        return $company?->id;
    }
}
