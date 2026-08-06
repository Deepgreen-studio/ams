<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Models\BusinessAnalyticsSnapshot;
use App\Domains\Analytics\Repositories\BusinessAnalyticsSnapshotRepository;
use App\Domains\Applications\Models\ApplicationAnalyticsDaily;
use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Enums\CustomerRiskLevel;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\SubscriptionStatus;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerAnalyticsSnapshot;
use App\Domains\Customers\Models\Subscription;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusinessAnalyticsService
{
    public function __construct(
        private readonly BusinessAnalyticsSnapshotRepository $snapshotRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);
        $metrics = $this->aggregateMetrics($companyId, $range['from'], $range['to']);
        $history = $this->ensureHistory($companyId, $range['from'], $range['to']);
        $forecast = $this->buildForecast($history, 14);

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'kpis' => [
                'customers_total' => $metrics['customers_total'],
                'customers_new' => $metrics['customers_new'],
                'customers_active' => $metrics['customers_active'],
                'subscriptions_active' => $metrics['subscriptions_active'],
                'subscriptions_new' => $metrics['subscriptions_new'],
                'mrr' => $metrics['mrr'],
                'revenue_period' => $metrics['revenue_period'],
                'application_sessions' => $metrics['application_sessions'],
                'application_active_users' => $metrics['application_active_users'],
                'feature_usage_count' => $metrics['feature_usage_count'],
                'support_tickets_open' => $metrics['support_tickets_open'],
                'support_tickets_new' => $metrics['support_tickets_new'],
                'avg_health_score' => $metrics['avg_health_score'],
                'at_risk_customers' => $metrics['at_risk_customers'],
            ],
            'charts' => [
                'customer_growth' => $this->seriesFromHistory($history, 'customers_total'),
                'new_customers' => $this->seriesFromHistory($history, 'customers_new'),
                'subscription_growth' => $this->seriesFromHistory($history, 'subscriptions_active'),
                'revenue_trend' => $this->seriesFromHistory($history, 'mrr'),
                'application_usage' => $this->seriesFromHistory($history, 'application_sessions'),
                'support_tickets' => $this->seriesFromHistory($history, 'support_tickets_new'),
                'health_score' => $this->seriesFromHistory($history, 'avg_health_score'),
            ],
            'forecast' => $forecast,
            'risk' => [
                'at_risk_customers' => $metrics['at_risk_customers'],
                'distribution' => $metrics['risk_distribution'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function customers(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);
        $metrics = $this->aggregateMetrics($companyId, $range['from'], $range['to']);
        $history = $this->ensureHistory($companyId, $range['from'], $range['to']);

        $byStatus = Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();

        $topAtRisk = CustomerAnalyticsSnapshot::query()
            ->when($companyId, function ($q) use ($companyId): void {
                $q->whereHas('customer', fn ($c) => $c->where('company_id', $companyId));
            })
            ->whereIn('id', function ($sub): void {
                $sub->selectRaw('MAX(id)')
                    ->from('customer_analytics_snapshots')
                    ->groupBy('customer_id');
            })
            ->whereIn('risk_level', [
                CustomerRiskLevel::High->value,
                CustomerRiskLevel::Critical->value,
                CustomerRiskLevel::Medium->value,
            ])
            ->with(['customer:id,uuid,display_name,email,status'])
            ->orderBy('health_score')
            ->limit(10)
            ->get()
            ->map(fn (CustomerAnalyticsSnapshot $row): array => [
                'customer_uuid' => $row->customer?->uuid,
                'display_name' => $row->customer?->display_name,
                'email' => $row->customer?->email,
                'health_score' => $row->health_score,
                'risk_level' => $row->risk_level?->value ?? $row->risk_level,
                'subscription_status' => $row->subscription_status,
            ])
            ->values()
            ->all();

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'kpis' => [
                'customers_total' => $metrics['customers_total'],
                'customers_new' => $metrics['customers_new'],
                'customers_active' => $metrics['customers_active'],
                'avg_health_score' => $metrics['avg_health_score'],
                'at_risk_customers' => $metrics['at_risk_customers'],
            ],
            'by_status' => $byStatus,
            'risk_distribution' => $metrics['risk_distribution'],
            'charts' => [
                'customer_growth' => $this->seriesFromHistory($history, 'customers_total'),
                'new_customers' => $this->seriesFromHistory($history, 'customers_new'),
                'active_customers' => $this->seriesFromHistory($history, 'customers_active'),
                'health_score' => $this->seriesFromHistory($history, 'avg_health_score'),
            ],
            'at_risk' => $topAtRisk,
            'daily_new_customers' => $this->dailyCustomerCreates($companyId, $range['from'], $range['to']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function revenue(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);
        $metrics = $this->aggregateMetrics($companyId, $range['from'], $range['to']);
        $history = $this->ensureHistory($companyId, $range['from'], $range['to']);

        $byPlan = $this->subscriptionsQuery($companyId)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->selectRaw('plan_type, COUNT(*) as count, COALESCE(SUM(amount),0) as revenue')
            ->groupBy('plan_type')
            ->get()
            ->map(fn ($row): array => [
                'plan_type' => $row->plan_type instanceof \BackedEnum ? $row->plan_type->value : $row->plan_type,
                'count' => (int) $row->count,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->values()
            ->all();

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'kpis' => [
                'mrr' => $metrics['mrr'],
                'revenue_period' => $metrics['revenue_period'],
                'subscriptions_active' => $metrics['subscriptions_active'],
                'subscriptions_new' => $metrics['subscriptions_new'],
                'subscriptions_total' => $metrics['subscriptions_total'],
                'arpu' => $metrics['subscriptions_active'] > 0
                    ? round($metrics['mrr'] / $metrics['subscriptions_active'], 2)
                    : 0,
            ],
            'by_plan' => $byPlan,
            'charts' => [
                'mrr' => $this->seriesFromHistory($history, 'mrr'),
                'revenue_period' => $this->seriesFromHistory($history, 'revenue_period'),
                'subscription_growth' => $this->seriesFromHistory($history, 'subscriptions_active'),
                'new_subscriptions' => $this->seriesFromHistory($history, 'subscriptions_new'),
            ],
            'forecast' => $this->buildForecast($history, 14, ['mrr', 'revenue_period', 'subscriptions_active']),
            'daily_revenue' => $this->dailySubscriptionRevenue($companyId, $range['from'], $range['to']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function applications(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);
        $metrics = $this->aggregateMetrics($companyId, $range['from'], $range['to']);
        $history = $this->ensureHistory($companyId, $range['from'], $range['to']);

        $usage = $this->applicationUsageSeries($companyId, $range['from'], $range['to']);
        $featureUsage = $this->featureUsageBreakdown($companyId);

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'kpis' => [
                'application_sessions' => $metrics['application_sessions'],
                'application_active_users' => $metrics['application_active_users'],
                'feature_usage_count' => $metrics['feature_usage_count'],
                'support_tickets_open' => $metrics['support_tickets_open'],
                'support_tickets_new' => $metrics['support_tickets_new'],
            ],
            'charts' => [
                'sessions' => $this->seriesFromHistory($history, 'application_sessions'),
                'active_users' => $this->seriesFromHistory($history, 'application_active_users'),
                'feature_usage' => $this->seriesFromHistory($history, 'feature_usage_count'),
                'support_tickets' => $this->seriesFromHistory($history, 'support_tickets_new'),
            ],
            'daily_usage' => $usage,
            'feature_breakdown' => $featureUsage,
            'support' => [
                'open' => $metrics['support_tickets_open'],
                'new' => $metrics['support_tickets_new'],
                'by_status' => $this->supportByStatus($companyId),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function growth(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 60);
        $history = $this->ensureHistory($companyId, $range['from'], $range['to']);

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'charts' => [
                'customer_growth' => $this->seriesFromHistory($history, 'customers_total'),
                'new_customers' => $this->seriesFromHistory($history, 'customers_new'),
                'active_customers' => $this->seriesFromHistory($history, 'customers_active'),
                'subscription_growth' => $this->seriesFromHistory($history, 'subscriptions_active'),
                'revenue_trend' => $this->seriesFromHistory($history, 'mrr'),
                'application_usage' => $this->seriesFromHistory($history, 'application_sessions'),
            ],
            'deltas' => $this->growthDeltas($history),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forecast(?string $company = null, ?string $from = null, ?string $to = null, int $horizonDays = 14): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 45);
        $history = $this->ensureHistory($companyId, $range['from'], $range['to']);

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
                'horizon_days' => $horizonDays,
            ],
            'forecast' => $this->buildForecast($history, $horizonDays),
            'historical' => [
                'mrr' => $this->seriesFromHistory($history, 'mrr'),
                'customers_total' => $this->seriesFromHistory($history, 'customers_total'),
                'subscriptions_active' => $this->seriesFromHistory($history, 'subscriptions_active'),
                'application_sessions' => $this->seriesFromHistory($history, 'application_sessions'),
            ],
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
        $metrics = $this->aggregateMetrics($companyId, $from, $day);
        $snapshot = $this->persistSnapshot($companyId, $day, $metrics);

        return [
            'snapshot' => $this->snapshotPayload($snapshot),
            'kpis' => [
                'customers_total' => $metrics['customers_total'],
                'customers_active' => $metrics['customers_active'],
                'mrr' => $metrics['mrr'],
                'avg_health_score' => $metrics['avg_health_score'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function aggregateMetrics(?int $companyId, Carbon $from, Carbon $to): array
    {
        $customersTotal = Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->count();

        $customersActive = Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', CustomerStatus::Active->value)
            ->count();

        $customersNew = Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        $subsQuery = $this->subscriptionsQuery($companyId);
        $subscriptionsTotal = (clone $subsQuery)->count();
        $subscriptionsActive = (clone $subsQuery)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->count();
        $subscriptionsNew = (clone $subsQuery)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        $mrr = (float) (clone $subsQuery)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->sum('amount');

        $revenuePeriod = (float) (clone $subsQuery)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->sum('amount');

        $appUsage = $this->sumApplicationUsage($companyId, $from, $to);
        $featureUsage = $this->featureUsageCount($companyId);

        $openStatuses = [
            SupportTicketStatus::Open->value,
            SupportTicketStatus::Pending->value,
            SupportTicketStatus::InProgress->value,
            SupportTicketStatus::WaitingForCustomer->value,
            SupportTicketStatus::Reopened->value,
        ];

        $ticketsOpen = SupportTicket::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', $openStatuses)
            ->count();

        $ticketsNew = SupportTicket::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        $health = $this->portfolioHealth($companyId);

        return [
            'customers_total' => $customersTotal,
            'customers_new' => $customersNew,
            'customers_active' => $customersActive,
            'subscriptions_total' => $subscriptionsTotal,
            'subscriptions_active' => $subscriptionsActive,
            'subscriptions_new' => $subscriptionsNew,
            'mrr' => round($mrr, 2),
            'revenue_period' => round($revenuePeriod, 2),
            'application_sessions' => $appUsage['sessions'],
            'application_active_users' => $appUsage['active_users'],
            'feature_usage_count' => $featureUsage,
            'support_tickets_open' => $ticketsOpen,
            'support_tickets_new' => $ticketsNew,
            'avg_health_score' => $health['avg_health_score'],
            'at_risk_customers' => $health['at_risk_customers'],
            'risk_distribution' => $health['risk_distribution'],
        ];
    }

    /**
     * @return Collection<int, BusinessAnalyticsSnapshot>
     */
    protected function ensureHistory(?int $companyId, Carbon $from, Carbon $to): Collection
    {
        $existing = $this->snapshotRepository->history($companyId, $from, $to)
            ->keyBy(fn (BusinessAnalyticsSnapshot $row) => optional($row->snapshot_date)->toDateString());

        // Always refresh today's snapshot so KPIs stay current.
        $today = now()->startOfDay();
        if ($today->gte($from->copy()->startOfDay()) && $today->lte($to->copy()->endOfDay())) {
            $dayFrom = $today->copy()->subDays(29);
            $metrics = $this->aggregateMetrics($companyId, $dayFrom, $today);
            $metrics['customers_new'] = Customer::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('created_at', $today->toDateString())
                ->count();
            $metrics['subscriptions_new'] = $this->subscriptionsQuery($companyId)
                ->whereDate('created_at', $today->toDateString())
                ->count();
            $metrics['revenue_period'] = round((float) $this->subscriptionsQuery($companyId)
                ->whereDate('created_at', $today->toDateString())
                ->sum('amount'), 2);
            $metrics['support_tickets_new'] = SupportTicket::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('created_at', $today->toDateString())
                ->count();
            $dayUsage = $this->sumApplicationUsage($companyId, $today->copy()->startOfDay(), $today->copy()->endOfDay());
            $metrics['application_sessions'] = $dayUsage['sessions'];
            $metrics['application_active_users'] = $dayUsage['active_users'];
            $existing->put($today->toDateString(), $this->persistSnapshot($companyId, $today, $metrics));
        }

        // Fill gaps in-memory (no bulk writes) so charts remain continuous.
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $synthetic = collect();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            if ($existing->has($key)) {
                $synthetic->put($key, $existing->get($key));
            } else {
                $synthetic->put($key, $this->makeSyntheticSnapshot($companyId, $cursor));
            }
            $cursor->addDay();
        }

        return $synthetic->sortKeys()->values();
    }

    protected function makeSyntheticSnapshot(?int $companyId, Carbon $day): BusinessAnalyticsSnapshot
    {
        $customersTotal = Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '<=', $day->copy()->endOfDay())
            ->count();

        $customersActive = Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', CustomerStatus::Active->value)
            ->where('created_at', '<=', $day->copy()->endOfDay())
            ->count();

        $customersNew = Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', $day->toDateString())
            ->count();

        $subsActive = $this->subscriptionsQuery($companyId)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->where('created_at', '<=', $day->copy()->endOfDay())
            ->count();

        $subsNew = $this->subscriptionsQuery($companyId)
            ->whereDate('created_at', $day->toDateString())
            ->count();

        $mrr = (float) $this->subscriptionsQuery($companyId)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->where('created_at', '<=', $day->copy()->endOfDay())
            ->sum('amount');

        $revenuePeriod = (float) $this->subscriptionsQuery($companyId)
            ->whereDate('created_at', $day->toDateString())
            ->sum('amount');

        $usage = $this->sumApplicationUsage($companyId, $day->copy()->startOfDay(), $day->copy()->endOfDay());
        $ticketsNew = SupportTicket::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', $day->toDateString())
            ->count();

        $snapshot = new BusinessAnalyticsSnapshot([
            'company_id' => $companyId,
            'snapshot_date' => $day->toDateString(),
            'customers_total' => $customersTotal,
            'customers_new' => $customersNew,
            'customers_active' => $customersActive,
            'subscriptions_total' => $subsActive,
            'subscriptions_active' => $subsActive,
            'subscriptions_new' => $subsNew,
            'mrr' => round($mrr, 2),
            'revenue_period' => round($revenuePeriod, 2),
            'application_sessions' => $usage['sessions'],
            'application_active_users' => $usage['active_users'],
            'feature_usage_count' => 0,
            'support_tickets_open' => 0,
            'support_tickets_new' => $ticketsNew,
            'avg_health_score' => 0,
            'at_risk_customers' => 0,
            'computed_at' => now(),
        ]);
        $snapshot->uuid = (string) \Illuminate\Support\Str::uuid();

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    protected function persistSnapshot(?int $companyId, Carbon $date, array $metrics): BusinessAnalyticsSnapshot
    {
        return $this->snapshotRepository->upsertForDate($companyId, $date, [
            'customers_total' => $metrics['customers_total'],
            'customers_new' => $metrics['customers_new'],
            'customers_active' => $metrics['customers_active'],
            'subscriptions_total' => $metrics['subscriptions_total'],
            'subscriptions_active' => $metrics['subscriptions_active'],
            'subscriptions_new' => $metrics['subscriptions_new'],
            'mrr' => $metrics['mrr'],
            'revenue_period' => $metrics['revenue_period'],
            'application_sessions' => $metrics['application_sessions'],
            'application_active_users' => $metrics['application_active_users'],
            'feature_usage_count' => $metrics['feature_usage_count'],
            'support_tickets_open' => $metrics['support_tickets_open'],
            'support_tickets_new' => $metrics['support_tickets_new'],
            'avg_health_score' => $metrics['avg_health_score'],
            'at_risk_customers' => $metrics['at_risk_customers'],
            'metrics' => [
                'risk_distribution' => $metrics['risk_distribution'] ?? [],
            ],
        ]);
    }

    /**
     * @return array{sessions: int, active_users: int}
     */
    protected function sumApplicationUsage(?int $companyId, Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('application_analytics_daily')) {
            return ['sessions' => 0, 'active_users' => 0];
        }

        $query = ApplicationAnalyticsDaily::query()
            ->whereBetween('metric_date', [$from->toDateString(), $to->toDateString()]);

        if ($companyId && Schema::hasColumn('applications', 'company_id')) {
            $query->whereHas('application', fn ($q) => $q->where('company_id', $companyId));
        }

        return [
            'sessions' => (int) (clone $query)->sum('sessions'),
            'active_users' => (int) (clone $query)->sum('active_users'),
        ];
    }

    protected function featureUsageCount(?int $companyId): int
    {
        $features = $this->subscriptionsQuery($companyId)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->whereNotNull('features')
            ->pluck('features');

        $count = 0;
        foreach ($features as $featureSet) {
            if (is_array($featureSet)) {
                $count += count($featureSet);
            }
        }

        if (Schema::hasTable('analytics_events')) {
            $count += (int) DB::table('analytics_events')
                ->where('event_name', 'like', 'feature.%')
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('occurred_at', '>=', now()->subDays(30))
                ->count();
        }

        return $count;
    }

    /**
     * @return array{avg_health_score: int, at_risk_customers: int, risk_distribution: array<string, int>}
     */
    protected function portfolioHealth(?int $companyId): array
    {
        if (! Schema::hasTable('customer_analytics_snapshots')) {
            return [
                'avg_health_score' => 0,
                'at_risk_customers' => 0,
                'risk_distribution' => [],
            ];
        }

        $latest = CustomerAnalyticsSnapshot::query()
            ->when($companyId, function ($q) use ($companyId): void {
                $q->whereHas('customer', fn ($c) => $c->where('company_id', $companyId));
            })
            ->whereIn('id', function ($sub): void {
                $sub->selectRaw('MAX(id)')
                    ->from('customer_analytics_snapshots')
                    ->groupBy('customer_id');
            })
            ->get(['health_score', 'risk_level']);

        if ($latest->isEmpty()) {
            return [
                'avg_health_score' => 0,
                'at_risk_customers' => 0,
                'risk_distribution' => [],
            ];
        }

        $distribution = $latest
            ->groupBy(fn ($row) => $row->risk_level?->value ?? $row->risk_level ?? 'unknown')
            ->map(fn (Collection $group) => $group->count())
            ->all();

        $atRisk = $latest->filter(function ($row): bool {
            $level = $row->risk_level?->value ?? $row->risk_level;

            return in_array($level, ['high', 'critical', 'medium'], true);
        })->count();

        return [
            'avg_health_score' => (int) round((float) $latest->avg('health_score')),
            'at_risk_customers' => $atRisk,
            'risk_distribution' => $distribution,
        ];
    }

    /**
     * @param  Collection<int, BusinessAnalyticsSnapshot>  $history
     * @return list<array{date: string, value: float|int}>
     */
    protected function seriesFromHistory(Collection $history, string $field): array
    {
        return $history->map(fn (BusinessAnalyticsSnapshot $row): array => [
            'date' => optional($row->snapshot_date)->toDateString(),
            'value' => is_numeric($row->{$field}) ? (str_contains($field, 'mrr') || str_contains($field, 'revenue')
                ? (float) $row->{$field}
                : (int) $row->{$field}) : 0,
        ])->values()->all();
    }

    /**
     * Simple linear regression forecast for selected metrics.
     *
     * @param  Collection<int, BusinessAnalyticsSnapshot>  $history
     * @param  list<string>  $fields
     * @return array<string, list<array{date: string, value: float, projected: bool}>>
     */
    protected function buildForecast(Collection $history, int $horizonDays = 14, array $fields = []): array
    {
        $fields = $fields !== [] ? $fields : ['mrr', 'customers_total', 'subscriptions_active', 'application_sessions'];
        $result = [];

        foreach ($fields as $field) {
            $points = $history->values();
            $n = $points->count();
            $historical = $this->seriesFromHistory($history, $field);
            $projected = [];

            if ($n >= 2) {
                $sumX = 0;
                $sumY = 0;
                $sumXY = 0;
                $sumXX = 0;
                foreach ($points as $i => $row) {
                    $x = $i;
                    $y = (float) $row->{$field};
                    $sumX += $x;
                    $sumY += $y;
                    $sumXY += $x * $y;
                    $sumXX += $x * $x;
                }
                $denom = ($n * $sumXX) - ($sumX * $sumX);
                $slope = $denom != 0.0 ? (($n * $sumXY) - ($sumX * $sumY)) / $denom : 0.0;
                $intercept = ($sumY - ($slope * $sumX)) / $n;

                $lastDate = optional($points->last()?->snapshot_date)->copy() ?? now();
                for ($d = 1; $d <= $horizonDays; $d++) {
                    $x = ($n - 1) + $d;
                    $value = max(0, $intercept + ($slope * $x));
                    $projected[] = [
                        'date' => $lastDate->copy()->addDays($d)->toDateString(),
                        'value' => round($value, $field === 'mrr' || str_contains($field, 'revenue') ? 2 : 0),
                        'projected' => true,
                    ];
                }
            }

            $result[$field] = [
                'historical' => array_map(fn ($p) => array_merge($p, ['projected' => false]), $historical),
                'projected' => $projected,
                'combined' => array_merge(
                    array_map(fn ($p) => array_merge($p, ['projected' => false]), $historical),
                    $projected
                ),
            ];
        }

        return $result;
    }

    /**
     * @param  Collection<int, BusinessAnalyticsSnapshot>  $history
     * @return array<string, mixed>
     */
    protected function growthDeltas(Collection $history): array
    {
        if ($history->count() < 2) {
            return [];
        }

        $first = $history->first();
        $last = $history->last();

        $delta = fn (string $field): array => [
            'from' => (float) $first->{$field},
            'to' => (float) $last->{$field},
            'change' => round((float) $last->{$field} - (float) $first->{$field}, 2),
            'change_percent' => (float) $first->{$field} > 0
                ? round((((float) $last->{$field} - (float) $first->{$field}) / (float) $first->{$field}) * 100, 2)
                : null,
        ];

        return [
            'customers_total' => $delta('customers_total'),
            'customers_active' => $delta('customers_active'),
            'subscriptions_active' => $delta('subscriptions_active'),
            'mrr' => $delta('mrr'),
            'application_sessions' => $delta('application_sessions'),
            'avg_health_score' => $delta('avg_health_score'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function dailyCustomerCreates(?int $companyId, Carbon $from, Carbon $to): array
    {
        $driver = DB::connection()->getDriverName();
        $bucket = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : 'DATE(created_at)';

        return Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw("{$bucket} as day, COUNT(*) as aggregate")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row): array => [
                'date' => (string) $row->day,
                'value' => (int) $row->aggregate,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function dailySubscriptionRevenue(?int $companyId, Carbon $from, Carbon $to): array
    {
        $driver = DB::connection()->getDriverName();
        $bucket = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : 'DATE(created_at)';

        return $this->subscriptionsQuery($companyId)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw("{$bucket} as day, COALESCE(SUM(amount),0) as revenue, COUNT(*) as count")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row): array => [
                'date' => (string) $row->day,
                'value' => round((float) $row->revenue, 2),
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Domains\Customers\Models\Subscription>
     */
    protected function subscriptionsQuery(?int $companyId)
    {
        return Subscription::query()
            ->when($companyId, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('company_id', $companyId)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function applicationUsageSeries(?int $companyId, Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('application_analytics_daily')) {
            return [];
        }

        $query = ApplicationAnalyticsDaily::query()
            ->whereBetween('metric_date', [$from->toDateString(), $to->toDateString()]);

        if ($companyId && Schema::hasColumn('applications', 'company_id')) {
            $query->whereHas('application', fn ($q) => $q->where('company_id', $companyId));
        }

        return $query
            ->selectRaw('metric_date as day, SUM(sessions) as sessions, SUM(active_users) as active_users')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row): array => [
                'date' => (string) $row->day,
                'sessions' => (int) $row->sessions,
                'active_users' => (int) $row->active_users,
            ])
            ->all();
    }

    /**
     * @return list<array{feature: string, count: int}>
     */
    protected function featureUsageBreakdown(?int $companyId): array
    {
        $counts = [];
        $features = $this->subscriptionsQuery($companyId)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->whereNotNull('features')
            ->pluck('features');

        foreach ($features as $featureSet) {
            if (! is_array($featureSet)) {
                continue;
            }
            foreach ($featureSet as $key => $value) {
                $name = is_string($key) ? $key : (is_string($value) ? $value : json_encode($value));
                if ($name === '' || $name === '0') {
                    continue;
                }
                if (is_bool($value) && $value === false) {
                    continue;
                }
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }

        arsort($counts);

        return collect($counts)->take(15)->map(fn ($count, $feature): array => [
            'feature' => (string) $feature,
            'count' => (int) $count,
        ])->values()->all();
    }

    /**
     * @return array<string, int>
     */
    protected function supportByStatus(?int $companyId): array
    {
        return SupportTicket::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshotPayload(BusinessAnalyticsSnapshot $snapshot): array
    {
        return [
            'uuid' => $snapshot->uuid,
            'snapshot_date' => optional($snapshot->snapshot_date)->toDateString(),
            'customers_total' => $snapshot->customers_total,
            'customers_new' => $snapshot->customers_new,
            'customers_active' => $snapshot->customers_active,
            'subscriptions_active' => $snapshot->subscriptions_active,
            'mrr' => (float) $snapshot->mrr,
            'avg_health_score' => $snapshot->avg_health_score,
            'computed_at' => optional($snapshot->computed_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    protected function resolveRange(?string $from, ?string $to, int $defaultDays): array
    {
        $end = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $start = $from ? Carbon::parse($from)->startOfDay() : $end->copy()->subDays($defaultDays - 1)->startOfDay();

        if ($start->gt($end)) {
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
