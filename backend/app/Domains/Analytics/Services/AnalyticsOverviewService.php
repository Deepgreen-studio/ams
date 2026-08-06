<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsDashboardKind;
use App\Domains\Analytics\Enums\AnalyticsDashboardStatus;
use App\Domains\Analytics\Repositories\AnalyticsDashboardRepository;
use App\Domains\Analytics\Repositories\AnalyticsEventRepository;
use App\Domains\Analytics\Repositories\AnalyticsReportRepository;
use App\Domains\Companies\Models\Company;

class AnalyticsOverviewService
{
    public function __construct(
        private readonly AnalyticsEventRepository $eventRepository,
        private readonly AnalyticsDashboardRepository $dashboardRepository,
        private readonly AnalyticsReportRepository $reportRepository,
        private readonly AnalyticsEventService $eventService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $byCategory = $this->eventRepository->countByCategory($normalized);
        $total = array_sum($byCategory);

        $categories = [];
        foreach (AnalyticsCategory::cases() as $category) {
            $count = (int) ($byCategory[$category->value] ?? 0);
            $categories[] = [
                'value' => $category->value,
                'label' => $category->label(),
                'description' => $category->description(),
                'event_count' => $count,
                'share' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        $dashboardFilters = array_filter([
            'company_id' => $normalized['company_id'] ?? null,
            'kind' => AnalyticsDashboardKind::Dashboard->value,
            'status' => AnalyticsDashboardStatus::Published->value,
            'per_page' => 6,
        ]);

        $savedViewFilters = array_filter([
            'company_id' => $normalized['company_id'] ?? null,
            'kind' => AnalyticsDashboardKind::SavedView->value,
            'per_page' => 6,
        ]);

        return [
            'period' => [
                'from' => $normalized['from'],
                'to' => $normalized['to'],
            ],
            'kpis' => [
                'total_events' => $total,
                'categories_active' => collect($byCategory)->filter(fn ($count) => $count > 0)->count(),
                'dashboards' => $this->dashboardRepository->filteredQuery([
                    'company_id' => $normalized['company_id'] ?? null,
                    'kind' => AnalyticsDashboardKind::Dashboard->value,
                ])->count(),
                'saved_views' => $this->dashboardRepository->filteredQuery([
                    'company_id' => $normalized['company_id'] ?? null,
                    'kind' => AnalyticsDashboardKind::SavedView->value,
                ])->count(),
                'report_definitions' => $this->reportRepository->filteredQuery([
                    'company_id' => $normalized['company_id'] ?? null,
                ])->count(),
            ],
            'categories' => $categories,
            'charts' => [
                'events_daily' => $this->eventRepository->dailyTrend($normalized),
                'top_events' => $this->eventRepository->topEventNames($normalized),
                'by_category' => $byCategory,
            ],
            'recent_dashboards' => $this->dashboardRepository->paginateFiltered($dashboardFilters),
            'saved_views' => $this->dashboardRepository->paginateFiltered($savedViewFilters),
            'supported_categories' => $this->eventService->categories(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function normalizeFilters(array $filters): array
    {
        if (! empty($filters['company']) && empty($filters['company_id'])) {
            $company = Company::query()->where('uuid', (string) $filters['company'])->first();
            $filters['company_id'] = $company?->id;
        }

        if (empty($filters['from'])) {
            $filters['from'] = now()->subDays(29)->toDateString();
        }

        if (empty($filters['to'])) {
            $filters['to'] = now()->toDateString();
        }

        return $filters;
    }
}
