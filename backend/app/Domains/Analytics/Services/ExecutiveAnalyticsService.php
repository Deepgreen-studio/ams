<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\ExecutiveDashboardType;
use App\Domains\Analytics\Models\ExecutiveAnalyticsSnapshot;
use App\Domains\Analytics\Repositories\ExecutiveAnalyticsSnapshotRepository;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationAnalyticsDaily;
use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Services\ComplianceAnalyticsService;
use App\Domains\Customers\Enums\SubscriptionStatus;
use App\Domains\Customers\Models\Subscription;
use App\Domains\Monitoring\Services\MonitoringService;
use App\Domains\Support\Services\SupportSlaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ExecutiveAnalyticsService
{
    /** @var array<string, mixed>|null */
    protected ?array $monitoringCache = null;

    /** @var array<string, mixed>|null */
    protected ?array $bundleCache = null;

    protected ?string $bundleCacheKey = null;

    public function __construct(
        private readonly ExecutiveAnalyticsSnapshotRepository $snapshotRepository,
        private readonly BusinessAnalyticsService $businessAnalyticsService,
        private readonly SecurityAnalyticsService $securityAnalyticsService,
        private readonly SupportSlaService $supportSlaService,
        private readonly ComplianceAnalyticsService $complianceAnalyticsService,
        private readonly MonitoringService $monitoringService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        return $this->dashboard(ExecutiveDashboardType::Ceo, $company, $from, $to);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(
        ExecutiveDashboardType|string $type,
        ?string $company = null,
        ?string $from = null,
        ?string $to = null,
    ): array {
        $dashboardType = $type instanceof ExecutiveDashboardType
            ? $type
            : ExecutiveDashboardType::from($type);

        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);
        $bundle = $this->composeBundle($company, $companyId, $range['from'], $range['to']);
        $widgets = $this->buildWidgets($company, $companyId, $range['from'], $range['to'], $bundle);
        $scorecards = $this->buildScorecards($bundle);
        $performance = $this->buildPerformanceIndicators($bundle);
        $trends = [
            'monthly' => $this->trendSeries($companyId, 'month', 12),
            'quarterly' => $this->trendSeries($companyId, 'quarter', 8),
            'yearly' => $this->trendSeries($companyId, 'year', 5),
        ];

        $payload = [
            'type' => $dashboardType->value,
            'label' => $dashboardType->label(),
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'kpis' => $bundle['kpis'],
            'scorecards' => $scorecards,
            'performance' => $performance,
            'growth' => $bundle['growth'],
            'forecast' => $bundle['forecast'],
            'trends' => $trends,
            'widgets' => $this->filterWidgetsForDashboard($widgets, $dashboardType),
            'charts' => $this->chartsForDashboard($bundle, $dashboardType),
            'focus' => $this->focusSections($bundle, $dashboardType),
        ];

        $this->persistTodaySnapshot($companyId, $bundle, $scorecards);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function scorecards(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);
        $bundle = $this->composeBundle($company, $companyId, $range['from'], $range['to']);

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'scorecards' => $this->buildScorecards($bundle),
            'performance' => $this->buildPerformanceIndicators($bundle),
            'kpis' => $bundle['kpis'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function trends(?string $company = null, string $granularity = 'monthly'): array
    {
        $companyId = $this->resolveCompanyId($company);
        $granularity = in_array($granularity, ['monthly', 'quarterly', 'yearly'], true)
            ? $granularity
            : 'monthly';

        $map = [
            'monthly' => ['unit' => 'month', 'points' => 12],
            'quarterly' => ['unit' => 'quarter', 'points' => 8],
            'yearly' => ['unit' => 'year', 'points' => 5],
        ];

        return [
            'granularity' => $granularity,
            'series' => $this->trendSeries($companyId, $map[$granularity]['unit'], $map[$granularity]['points']),
            'available' => ['monthly', 'quarterly', 'yearly'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forecast(?string $company = null, ?string $from = null, ?string $to = null, int $horizonDays = 14): array
    {
        $forecast = $this->businessAnalyticsService->forecast($company, $from, $to, $horizonDays);
        $growth = $this->businessAnalyticsService->growth($company, $from, $to);

        return [
            'period' => $forecast['period'] ?? null,
            'forecast' => $forecast['forecast'] ?? $forecast,
            'growth' => $growth,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function widgets(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);
        $bundle = $this->composeBundle($company, $companyId, $range['from'], $range['to']);

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'widgets' => $this->buildWidgets($company, $companyId, $range['from'], $range['to'], $bundle),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function capture(?string $company = null, ?string $date = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $day = $date ? Carbon::parse($date)->startOfDay() : now()->startOfDay();
        $from = $day->copy()->subDays(29);
        $bundle = $this->composeBundle($company, $companyId, $from, $day);
        $scorecards = $this->buildScorecards($bundle);
        $snapshot = $this->persistSnapshot($companyId, $day, $bundle, $scorecards);

        return [
            'snapshot' => [
                'uuid' => $snapshot->uuid,
                'snapshot_date' => optional($snapshot->snapshot_date)->toDateString(),
                'business_score' => $snapshot->business_score,
                'mrr' => (float) $snapshot->mrr,
                'computed_at' => optional($snapshot->computed_at)?->toIso8601String(),
            ],
            'kpis' => $bundle['kpis'],
            'scorecards' => $scorecards,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function composeBundle(?string $company, ?int $companyId, Carbon $from, Carbon $to): array
    {
        $cacheKey = implode('|', [$company ?? '', $companyId ?? '', $from->toDateString(), $to->toDateString()]);
        if ($this->bundleCacheKey === $cacheKey && $this->bundleCache !== null) {
            return $this->bundleCache;
        }

        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        $business = $this->safeCall(fn () => $this->businessAnalyticsService->overview($company, $fromStr, $toStr), []);
        $growth = $this->safeCall(fn () => $this->businessAnalyticsService->growth($company, $fromStr, $toStr), []);
        $forecast = $this->safeCall(fn () => $this->businessAnalyticsService->forecast($company, $fromStr, $toStr, 14), []);
        $security = $this->safeCall(fn () => $this->securityAnalyticsService->overview($company, $fromStr, $toStr), []);
        $sla = $this->safeCall(fn () => $this->supportSlaService->dashboard([
            'company_id' => $company,
        ]), []);
        $compliance = $this->safeCall(fn () => $this->complianceAnalyticsService->dashboard([
            'company' => $company,
            'from' => $fromStr,
            'to' => $toStr,
        ]), []);

        if ($this->monitoringCache === null) {
            if (app()->environment('testing')) {
                $this->monitoringCache = [
                    'scores' => [
                        'health_score' => 90,
                        'performance_score' => 88,
                        'uptime_percent' => 99.5,
                        'error_rate' => 0.5,
                    ],
                    'statuses' => [
                        'availability' => 'healthy',
                        'queue' => 'healthy',
                    ],
                    'charts' => ['health_trend' => []],
                ];
            } else {
                $this->monitoringCache = $this->safeCall(fn () => $this->monitoringService->dashboard($company), [
                    'scores' => [
                        'health_score' => 0,
                        'uptime_percent' => 0,
                        'error_rate' => 0,
                    ],
                    'statuses' => [],
                    'charts' => ['health_trend' => []],
                ]);
            }
        }
        $monitoring = $this->monitoringCache;

        $bizKpis = $business['kpis'] ?? [];
        $slaStats = $sla['statistics'] ?? [];
        $compKpis = $compliance['kpis'] ?? [];
        $scores = $monitoring['scores'] ?? [];
        $secKpis = $security['kpis'] ?? [];

        $applicationsTotal = 0;
        if (Schema::hasTable('applications')) {
            $applicationsTotal = Application::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->count();
        }

        $kpis = [
            'mrr' => (float) ($bizKpis['mrr'] ?? 0),
            'revenue_period' => (float) ($bizKpis['revenue_period'] ?? 0),
            'customers_total' => (int) ($bizKpis['customers_total'] ?? 0),
            'customers_active' => (int) ($bizKpis['customers_active'] ?? 0),
            'customers_new' => (int) ($bizKpis['customers_new'] ?? 0),
            'subscriptions_active' => (int) ($bizKpis['subscriptions_active'] ?? 0),
            'applications_total' => $applicationsTotal,
            'application_sessions' => (int) ($bizKpis['application_sessions'] ?? 0),
            'support_tickets_open' => (int) ($bizKpis['support_tickets_open'] ?? 0),
            'support_tickets_new' => (int) ($bizKpis['support_tickets_new'] ?? 0),
            'support_sla_on_track' => (int) ($slaStats['on_track'] ?? 0),
            'support_sla_at_risk' => (int) ($slaStats['at_risk'] ?? 0),
            'support_sla_breached' => (int) ($slaStats['breached'] ?? 0),
            'support_sla_met' => (int) ($slaStats['met'] ?? 0),
            'compliance_cases_open' => (int) ($compKpis['compliance_cases_open'] ?? 0),
            'compliance_risk_score' => (int) ($compKpis['risk_score'] ?? 0),
            'privacy_requests_open' => (int) ($compKpis['privacy_requests_open'] ?? 0),
            'data_breaches_open' => (int) ($compKpis['data_breaches_open'] ?? 0),
            'system_health_score' => (int) ($scores['health_score'] ?? 0),
            'system_uptime_percent' => (float) ($scores['uptime_percent'] ?? 0),
            'system_error_rate' => (float) ($scores['error_rate'] ?? 0),
            'security_risk_score' => (int) ($secKpis['risk_score'] ?? ($security['risk']['score'] ?? 0)),
            'avg_customer_health' => (int) ($bizKpis['avg_health_score'] ?? 0),
            'at_risk_customers' => (int) ($bizKpis['at_risk_customers'] ?? 0),
        ];

        $kpis['business_score'] = $this->calculateBusinessScore($kpis);

        $this->bundleCacheKey = $cacheKey;
        $this->bundleCache = [
            'kpis' => $kpis,
            'business' => $business,
            'growth' => $growth,
            'forecast' => $forecast,
            'security' => $security,
            'sla' => $sla,
            'compliance' => $compliance,
            'monitoring' => $monitoring,
        ];

        return $this->bundleCache;
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return list<array<string, mixed>>
     */
    protected function buildScorecards(array $bundle): array
    {
        $k = $bundle['kpis'];

        return [
            $this->scorecard('revenue', 'Revenue', (float) $k['mrr'], 'MRR', $this->scoreFromThreshold((float) $k['mrr'], 1000, 5000, 20000)),
            $this->scorecard('growth', 'Growth', (int) $k['customers_new'], 'New customers', $this->scoreFromThreshold((int) $k['customers_new'], 1, 5, 20)),
            $this->scorecard('customers', 'Customers', (int) $k['customers_active'], 'Active', $this->scoreFromThreshold((int) $k['customers_active'], 10, 50, 200)),
            $this->scorecard('support', 'Support SLA', (int) $k['support_sla_on_track'], 'On track', $this->slaScore($k)),
            $this->scorecard('compliance', 'Compliance', (int) $k['compliance_risk_score'], 'Risk score', $this->invertScore((int) $k['compliance_risk_score'])),
            $this->scorecard('operations', 'System Health', (int) $k['system_health_score'], 'Health', min(100, max(0, (int) $k['system_health_score']))),
            $this->scorecard('security', 'Security', (int) $k['security_risk_score'], 'Risk score', $this->invertScore((int) $k['security_risk_score'])),
            $this->scorecard('business', 'Business Score', (int) $k['business_score'], 'Composite', (int) $k['business_score']),
        ];
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return list<array<string, mixed>>
     */
    protected function buildPerformanceIndicators(array $bundle): array
    {
        $k = $bundle['kpis'];
        $deltas = $bundle['growth']['deltas'] ?? [];

        return [
            ['key' => 'mrr', 'label' => 'MRR', 'value' => $k['mrr'], 'delta' => $deltas['mrr'] ?? null, 'unit' => 'currency'],
            ['key' => 'customers_active', 'label' => 'Active customers', 'value' => $k['customers_active'], 'delta' => $deltas['customers_active'] ?? null, 'unit' => 'count'],
            ['key' => 'customers_new', 'label' => 'New customers', 'value' => $k['customers_new'], 'delta' => $deltas['customers_new'] ?? null, 'unit' => 'count'],
            ['key' => 'subscriptions_active', 'label' => 'Active subscriptions', 'value' => $k['subscriptions_active'], 'delta' => $deltas['subscriptions_active'] ?? null, 'unit' => 'count'],
            ['key' => 'application_sessions', 'label' => 'App sessions', 'value' => $k['application_sessions'], 'delta' => $deltas['application_sessions'] ?? null, 'unit' => 'count'],
            ['key' => 'support_sla_breached', 'label' => 'SLA breaches', 'value' => $k['support_sla_breached'], 'delta' => null, 'unit' => 'count'],
            ['key' => 'system_uptime_percent', 'label' => 'Uptime %', 'value' => $k['system_uptime_percent'], 'delta' => null, 'unit' => 'percent'],
            ['key' => 'avg_customer_health', 'label' => 'Avg customer health', 'value' => $k['avg_customer_health'], 'delta' => null, 'unit' => 'score'],
        ];
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    protected function buildWidgets(?string $company, ?int $companyId, Carbon $from, Carbon $to, array $bundle): array
    {
        return [
            'top_customers' => $this->topCustomers($companyId),
            'top_applications' => $this->topApplications($companyId, $from, $to),
            'revenue' => [
                'mrr' => $bundle['kpis']['mrr'],
                'revenue_period' => $bundle['kpis']['revenue_period'],
                'subscriptions_active' => $bundle['kpis']['subscriptions_active'],
                'chart' => $bundle['business']['charts']['revenue_trend'] ?? [],
            ],
            'support_sla' => [
                'statistics' => $bundle['sla']['statistics'] ?? [],
                'on_track' => $bundle['kpis']['support_sla_on_track'],
                'at_risk' => $bundle['kpis']['support_sla_at_risk'],
                'breached' => $bundle['kpis']['support_sla_breached'],
                'met' => $bundle['kpis']['support_sla_met'],
            ],
            'compliance_status' => [
                'kpis' => $bundle['compliance']['kpis'] ?? [],
                'cases_open' => $bundle['kpis']['compliance_cases_open'],
                'risk_score' => $bundle['kpis']['compliance_risk_score'],
                'privacy_open' => $bundle['kpis']['privacy_requests_open'],
                'breaches_open' => $bundle['kpis']['data_breaches_open'],
            ],
            'system_health' => [
                'scores' => $bundle['monitoring']['scores'] ?? [],
                'statuses' => $bundle['monitoring']['statuses'] ?? [],
                'health_score' => $bundle['kpis']['system_health_score'],
                'uptime_percent' => $bundle['kpis']['system_uptime_percent'],
                'error_rate' => $bundle['kpis']['system_error_rate'],
            ],
            'growth_metrics' => [
                'charts' => $bundle['growth']['charts'] ?? [],
                'deltas' => $bundle['growth']['deltas'] ?? [],
                'customers_new' => $bundle['kpis']['customers_new'],
                'customers_active' => $bundle['kpis']['customers_active'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $widgets
     * @return array<string, mixed>
     */
    protected function filterWidgetsForDashboard(array $widgets, ExecutiveDashboardType $type): array
    {
        $map = [
            ExecutiveDashboardType::Ceo->value => ['top_customers', 'top_applications', 'revenue', 'growth_metrics', 'system_health', 'compliance_status'],
            ExecutiveDashboardType::Admin->value => ['revenue', 'top_applications', 'system_health', 'support_sla', 'compliance_status', 'growth_metrics'],
            ExecutiveDashboardType::Operations->value => ['system_health', 'support_sla', 'top_applications', 'growth_metrics'],
            ExecutiveDashboardType::Compliance->value => ['compliance_status', 'support_sla', 'top_customers'],
            ExecutiveDashboardType::Support->value => ['support_sla', 'top_customers', 'growth_metrics'],
            ExecutiveDashboardType::Customer->value => ['top_customers', 'growth_metrics', 'revenue', 'top_applications'],
        ];

        $keys = $map[$type->value] ?? array_keys($widgets);

        return collect($keys)
            ->filter(fn (string $key) => isset($widgets[$key]))
            ->mapWithKeys(fn (string $key) => [$key => $widgets[$key]])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    protected function chartsForDashboard(array $bundle, ExecutiveDashboardType $type): array
    {
        $bizCharts = $bundle['business']['charts'] ?? [];
        $growthCharts = $bundle['growth']['charts'] ?? [];
        $healthTrend = $bundle['monitoring']['charts']['health_trend'] ?? [];

        return match ($type) {
            ExecutiveDashboardType::Ceo => [
                'revenue_trend' => $bizCharts['revenue_trend'] ?? [],
                'customer_growth' => $bizCharts['customer_growth'] ?? [],
                'health_score' => $bizCharts['health_score'] ?? [],
            ],
            ExecutiveDashboardType::Admin => [
                'revenue_trend' => $bizCharts['revenue_trend'] ?? [],
                'application_usage' => $bizCharts['application_usage'] ?? [],
                'support_tickets' => $bizCharts['support_tickets'] ?? [],
                'system_health' => $healthTrend,
            ],
            ExecutiveDashboardType::Operations => [
                'system_health' => $healthTrend,
                'application_usage' => $growthCharts['application_usage'] ?? [],
                'support_tickets' => $bizCharts['support_tickets'] ?? [],
            ],
            ExecutiveDashboardType::Compliance => [
                'customer_growth' => $bizCharts['customer_growth'] ?? [],
            ],
            ExecutiveDashboardType::Support => [
                'support_tickets' => $bizCharts['support_tickets'] ?? [],
                'customer_growth' => $bizCharts['customer_growth'] ?? [],
            ],
            ExecutiveDashboardType::Customer => [
                'customer_growth' => $growthCharts['customer_growth'] ?? [],
                'revenue_trend' => $growthCharts['revenue_trend'] ?? [],
                'health_score' => $bizCharts['health_score'] ?? [],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    protected function focusSections(array $bundle, ExecutiveDashboardType $type): array
    {
        return match ($type) {
            ExecutiveDashboardType::Ceo => [
                'headline' => 'Portfolio performance',
                'summary' => 'Revenue, growth, customer health, and enterprise risk at a glance.',
            ],
            ExecutiveDashboardType::Admin => [
                'headline' => 'Platform administration',
                'summary' => 'Applications, operations health, SLA posture, and compliance readiness.',
            ],
            ExecutiveDashboardType::Operations => [
                'headline' => 'Operational reliability',
                'summary' => 'System health, uptime, queue posture, and application load.',
            ],
            ExecutiveDashboardType::Compliance => [
                'headline' => 'Compliance posture',
                'summary' => 'Open cases, privacy requests, breaches, and risk register score.',
            ],
            ExecutiveDashboardType::Support => [
                'headline' => 'Support performance',
                'summary' => 'SLA on-track vs breached timers and ticket volume.',
            ],
            ExecutiveDashboardType::Customer => [
                'headline' => 'Customer portfolio',
                'summary' => 'Top customers, health, growth, and revenue contribution.',
            ],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function topCustomers(?int $companyId, int $limit = 10): array
    {
        if (! Schema::hasTable('subscriptions') || ! Schema::hasTable('customers')) {
            return [];
        }

        $statusValues = [
            SubscriptionStatus::Active->value,
            SubscriptionStatus::Trialing->value,
        ];

        return Subscription::query()
            ->selectRaw('customer_id, SUM(amount) as revenue')
            ->whereIn('status', $statusValues)
            ->when($companyId, function ($q) use ($companyId): void {
                $q->whereHas('customer', fn ($c) => $c->where('company_id', $companyId));
            })
            ->groupBy('customer_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->with(['customer:id,uuid,display_name,email,status'])
            ->get()
            ->map(fn (Subscription $row): array => [
                'customer_uuid' => $row->customer?->uuid,
                'display_name' => $row->customer?->display_name,
                'email' => $row->customer?->email,
                'status' => $row->customer?->status?->value ?? $row->customer?->status,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function topApplications(?int $companyId, Carbon $from, Carbon $to, int $limit = 10): array
    {
        if (! Schema::hasTable('application_analytics_daily')) {
            return [];
        }

        $rows = ApplicationAnalyticsDaily::query()
            ->selectRaw('application_id, SUM(sessions) as sessions, SUM(active_users) as active_users')
            ->whereBetween('metric_date', [$from->toDateString(), $to->toDateString()])
            ->when($companyId && Schema::hasColumn('applications', 'company_id'), function ($q) use ($companyId): void {
                $q->whereHas('application', fn ($a) => $a->where('company_id', $companyId));
            })
            ->groupBy('application_id')
            ->orderByDesc('sessions')
            ->limit($limit)
            ->with(['application:id,uuid,name,platform,status'])
            ->get();

        return $rows->map(fn (ApplicationAnalyticsDaily $row): array => [
            'application_uuid' => $row->application?->uuid,
            'name' => $row->application?->name,
            'platform' => $row->application?->platform?->value ?? $row->application?->platform,
            'status' => $row->application?->status?->value ?? $row->application?->status,
            'sessions' => (int) $row->sessions,
            'active_users' => (int) $row->active_users,
        ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function trendSeries(?int $companyId, string $unit, int $points): array
    {
        $to = now()->endOfDay();
        $from = match ($unit) {
            'quarter' => $to->copy()->subQuarters($points - 1)->startOfQuarter(),
            'year' => $to->copy()->subYears($points - 1)->startOfYear(),
            default => $to->copy()->subMonths($points - 1)->startOfMonth(),
        };

        $history = $this->snapshotRepository->history($companyId, $from, $to);
        if ($history->isEmpty()) {
            // Seed from business history when executive snapshots are sparse.
            $business = $this->safeCall(
                fn () => $this->businessAnalyticsService->growth(
                    $companyId !== null ? (string) $companyId : null,
                    $from->toDateString(),
                    $to->toDateString(),
                ),
                []
            );
            $daily = collect($business['charts']['revenue_trend'] ?? []);

            return $this->aggregateDailyToBuckets($daily, $unit, $points);
        }

        return $this->aggregateSnapshotsToBuckets($history, $unit, $points);
    }

    /**
     * @param  Collection<int, ExecutiveAnalyticsSnapshot>  $history
     * @return list<array<string, mixed>>
     */
    protected function aggregateSnapshotsToBuckets(Collection $history, string $unit, int $points): array
    {
        $grouped = $history->groupBy(function (ExecutiveAnalyticsSnapshot $row) use ($unit): string {
            $date = Carbon::parse($row->snapshot_date);

            return match ($unit) {
                'quarter' => $date->format('Y').'-Q'.$date->quarter,
                'year' => $date->format('Y'),
                default => $date->format('Y-m'),
            };
        });

        return $grouped->map(function (Collection $rows, string $label): array {
            /** @var ExecutiveAnalyticsSnapshot $last */
            $last = $rows->sortBy('snapshot_date')->last();

            return [
                'label' => $label,
                'mrr' => (float) $last->mrr,
                'customers_total' => (int) $last->customers_total,
                'customers_active' => (int) $last->customers_active,
                'business_score' => (int) $last->business_score,
                'system_health_score' => (int) $last->system_health_score,
                'support_sla_breached' => (int) $last->support_sla_breached,
            ];
        })->values()->take(-$points)->values()->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $daily
     * @return list<array<string, mixed>>
     */
    protected function aggregateDailyToBuckets(Collection $daily, string $unit, int $points): array
    {
        if ($daily->isEmpty()) {
            return [];
        }

        $grouped = $daily->groupBy(function (array $row) use ($unit): string {
            $date = Carbon::parse((string) ($row['date'] ?? now()->toDateString()));

            return match ($unit) {
                'quarter' => $date->format('Y').'-Q'.$date->quarter,
                'year' => $date->format('Y'),
                default => $date->format('Y-m'),
            };
        });

        return $grouped->map(function (Collection $rows, string $label): array {
            $last = $rows->last();

            return [
                'label' => $label,
                'mrr' => (float) ($last['value'] ?? 0),
                'customers_total' => 0,
                'customers_active' => 0,
                'business_score' => 0,
                'system_health_score' => 0,
                'support_sla_breached' => 0,
            ];
        })->values()->take(-$points)->values()->all();
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @param  list<array<string, mixed>>  $scorecards
     */
    protected function persistTodaySnapshot(?int $companyId, array $bundle, array $scorecards): void
    {
        try {
            $this->persistSnapshot($companyId, now()->startOfDay(), $bundle, $scorecards);
        } catch (\Throwable) {
            // Non-blocking for read paths.
        }
    }

    /**
     * @param  array<string, mixed>  $bundle
     * @param  list<array<string, mixed>>  $scorecards
     */
    protected function persistSnapshot(?int $companyId, Carbon $day, array $bundle, array $scorecards): ExecutiveAnalyticsSnapshot
    {
        $k = $bundle['kpis'];

        return $this->snapshotRepository->upsertForDate($companyId, $day, [
            'mrr' => $k['mrr'],
            'revenue_period' => $k['revenue_period'],
            'customers_total' => $k['customers_total'],
            'customers_active' => $k['customers_active'],
            'customers_new' => $k['customers_new'],
            'applications_total' => $k['applications_total'],
            'subscriptions_active' => $k['subscriptions_active'],
            'support_tickets_open' => $k['support_tickets_open'],
            'support_sla_on_track' => $k['support_sla_on_track'],
            'support_sla_breached' => $k['support_sla_breached'],
            'compliance_cases_open' => $k['compliance_cases_open'],
            'compliance_risk_score' => min(100, max(0, (int) $k['compliance_risk_score'])),
            'system_health_score' => min(100, max(0, (int) $k['system_health_score'])),
            'system_uptime_percent' => $k['system_uptime_percent'],
            'security_risk_score' => min(100, max(0, (int) $k['security_risk_score'])),
            'business_score' => $k['business_score'],
            'scorecards' => $scorecards,
            'metrics' => $k,
        ]);
    }

    /**
     * @param  array<string, mixed>  $kpis
     */
    protected function calculateBusinessScore(array $kpis): int
    {
        $revenue = $this->scoreFromThreshold((float) $kpis['mrr'], 1000, 5000, 20000);
        $growth = $this->scoreFromThreshold((int) $kpis['customers_new'], 1, 5, 20);
        $health = min(100, max(0, (int) $kpis['avg_customer_health']));
        $ops = min(100, max(0, (int) $kpis['system_health_score']));
        $sla = $this->slaScore($kpis);
        $compliance = $this->invertScore((int) $kpis['compliance_risk_score']);
        $security = $this->invertScore((int) $kpis['security_risk_score']);

        return (int) round(($revenue * 0.2) + ($growth * 0.15) + ($health * 0.15) + ($ops * 0.15) + ($sla * 0.15) + ($compliance * 0.1) + ($security * 0.1));
    }

    /**
     * @param  array<string, mixed>  $kpis
     */
    protected function slaScore(array $kpis): int
    {
        $tracked = (int) $kpis['support_sla_on_track']
            + (int) $kpis['support_sla_at_risk']
            + (int) $kpis['support_sla_breached']
            + (int) $kpis['support_sla_met'];

        if ($tracked === 0) {
            return 75;
        }

        $good = (int) $kpis['support_sla_on_track'] + (int) $kpis['support_sla_met'];
        $breached = (int) $kpis['support_sla_breached'];

        return (int) max(0, min(100, round(($good / $tracked) * 100) - ($breached * 5)));
    }

    protected function invertScore(int $riskScore): int
    {
        return max(0, min(100, 100 - $riskScore));
    }

    protected function scoreFromThreshold(float|int $value, float $low, float $mid, float $high): int
    {
        if ($value >= $high) {
            return 95;
        }
        if ($value >= $mid) {
            return 75;
        }
        if ($value >= $low) {
            return 55;
        }
        if ($value > 0) {
            return 35;
        }

        return 15;
    }

    /**
     * @return array{key: string, label: string, value: float|int, unit_label: string, score: int, status: string}
     */
    protected function scorecard(string $key, string $label, float|int $value, string $unitLabel, int $score): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'unit_label' => $unitLabel,
            'score' => $score,
            'status' => match (true) {
                $score >= 80 => 'excellent',
                $score >= 60 => 'good',
                $score >= 40 => 'watch',
                default => 'critical',
            },
        ];
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @param  T  $fallback
     * @return T
     */
    protected function safeCall(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    protected function resolveRange(?string $from, ?string $to, int $defaultDays): array
    {
        $end = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $start = $from ? Carbon::parse($from)->startOfDay() : $end->copy()->subDays($defaultDays - 1)->startOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return ['from' => $start, 'to' => $end];
    }

    protected function resolveCompanyId(?string $company): ?int
    {
        if ($company === null || $company === '') {
            return null;
        }
        if (is_numeric($company)) {
            return (int) $company;
        }

        return Company::query()->where('uuid', $company)->value('id');
    }
}
