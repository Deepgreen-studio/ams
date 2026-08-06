<?php

namespace App\Domains\Compliance\Repositories;

use App\Domains\Audit\Models\ActivityLog;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Compliance\Enums\ComplianceCaseStatus;
use App\Domains\Compliance\Enums\ConsentStatus;
use App\Domains\Compliance\Enums\DataBreachStatus;
use App\Domains\Compliance\Enums\PrivacyRequestStatus;
use App\Domains\Compliance\Enums\RiskRegisterStatus;
use App\Domains\Compliance\Models\ComplianceCase;
use App\Domains\Compliance\Models\DataBreach;
use App\Domains\Compliance\Models\DpiaAssessment;
use App\Domains\Compliance\Models\PolicyDocument;
use App\Domains\Compliance\Models\PolicyVersion;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Compliance\Models\RiskRegister;
use App\Domains\Compliance\Models\UserConsent;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ComplianceAnalyticsRepository
{
    public function __construct(
        private readonly CompanyRepository $companyRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{company_id: ?int, from: Carbon, to: Carbon, days: int}
     */
    public function normalizeFilters(array $filters): array
    {
        $companyId = null;
        if (! empty($filters['company']) || ! empty($filters['company_id'])) {
            $company = $this->companyRepository->findByIdentifierOrFail(
                (string) ($filters['company'] ?? $filters['company_id'])
            );
            $companyId = $company->id;
        }

        $to = ! empty($filters['to'])
            ? Carbon::parse((string) $filters['to'])->endOfDay()
            : now()->endOfDay();
        $from = ! empty($filters['from'])
            ? Carbon::parse((string) $filters['from'])->startOfDay()
            : $to->copy()->subDays(29)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $days = max(1, $from->diffInDays($to) + 1);

        return [
            'company_id' => $companyId,
            'from' => $from,
            'to' => $to,
            'days' => $days,
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    public function overview(array $scope): array
    {
        $privacy = $this->privacyMetrics($scope);
        $cases = $this->caseMetrics($scope);
        $risks = $this->riskMetrics($scope);
        $policies = $this->policyMetrics($scope);
        $consents = $this->consentMetrics($scope);
        $breaches = $this->breachMetrics($scope);
        $audit = $this->auditMetrics($scope);

        return [
            'period' => [
                'from' => $scope['from']->toDateString(),
                'to' => $scope['to']->toDateString(),
                'days' => $scope['days'],
            ],
            'kpis' => [
                'privacy_requests' => $privacy['total'],
                'privacy_requests_open' => $privacy['open'],
                'average_resolution_hours' => $privacy['average_resolution_hours'],
                'compliance_cases' => $cases['total'],
                'compliance_cases_open' => $cases['open'],
                'risk_score' => $risks['average_score'],
                'open_risks' => $risks['open'],
                'closed_risks' => $risks['closed'],
                'policy_updates' => $policies['updates'],
                'consent_granted' => $consents['granted'],
                'consent_withdrawn' => $consents['withdrawn'],
                'data_breaches' => $breaches['total'],
                'data_breaches_open' => $breaches['open'],
                'audit_events' => $audit['total'],
            ],
            'privacy' => $privacy,
            'cases' => $cases,
            'risks' => $risks,
            'policies' => $policies,
            'consents' => $consents,
            'breaches' => $breaches,
            'audit' => $audit,
            'trends' => [
                'labels' => $this->dateLabels($scope),
                'privacy_requests' => $this->dailyCounts(PrivacyRequest::query(), $scope),
                'compliance_cases' => $this->dailyCounts(ComplianceCase::query(), $scope),
                'data_breaches' => $this->dailyCounts(DataBreach::query(), $scope),
                'policy_updates' => $this->dailyCounts(PolicyVersion::query(), $scope, 'created_at'),
                'consent_events' => $this->dailyCounts(UserConsent::query(), $scope),
                'audit_events' => $this->dailyAuditCounts($scope),
            ],
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    public function riskCharts(array $scope): array
    {
        $metrics = $this->riskMetrics($scope);

        return [
            'period' => [
                'from' => $scope['from']->toDateString(),
                'to' => $scope['to']->toDateString(),
            ],
            'summary' => $metrics,
            'by_level' => $metrics['by_level'],
            'by_status' => $metrics['by_status'],
            'trends' => [
                'labels' => $this->dateLabels($scope),
                'opened' => $this->dailyCounts(RiskRegister::query(), $scope),
                'closed' => $this->dailyCounts(
                    RiskRegister::query()->where('status', RiskRegisterStatus::Closed->value),
                    $scope,
                    'updated_at'
                ),
            ],
            'top_risks' => RiskRegister::query()
                ->when($scope['company_id'], fn (Builder $q) => $q->where('company_id', $scope['company_id']))
                ->whereIn('status', RiskRegisterStatus::activeValues())
                ->orderByDesc('risk_score')
                ->limit(10)
                ->get(['uuid', 'risk_number', 'title', 'risk_score', 'risk_level', 'status', 'updated_at'])
                ->map(fn (RiskRegister $risk) => [
                    'uuid' => $risk->uuid,
                    'risk_number' => $risk->risk_number,
                    'title' => $risk->title,
                    'risk_score' => (int) $risk->risk_score,
                    'risk_level' => $risk->risk_level?->value ?? $risk->risk_level,
                    'status' => $risk->status?->value ?? $risk->status,
                    'updated_at' => $risk->updated_at,
                ])
                ->all(),
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    public function gdprReport(array $scope): array
    {
        return [
            'period' => [
                'from' => $scope['from']->toDateString(),
                'to' => $scope['to']->toDateString(),
            ],
            'privacy_requests' => $this->privacyMetrics($scope),
            'data_breaches' => $this->breachMetrics($scope),
            'dpia' => [
                'total' => $this->scoped(DpiaAssessment::query(), $scope)->count(),
                'by_status' => $this->groupCounts(
                    $this->scoped(DpiaAssessment::query(), $scope),
                    'status'
                ),
            ],
            'cases' => $this->caseMetrics($scope),
            'trends' => [
                'labels' => $this->dateLabels($scope),
                'privacy_requests' => $this->dailyCounts(PrivacyRequest::query(), $scope),
                'data_breaches' => $this->dailyCounts(DataBreach::query(), $scope),
                'dpia' => $this->dailyCounts(DpiaAssessment::query(), $scope),
            ],
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    public function consentReport(array $scope): array
    {
        $metrics = $this->consentMetrics($scope);

        return [
            'period' => [
                'from' => $scope['from']->toDateString(),
                'to' => $scope['to']->toDateString(),
            ],
            'summary' => $metrics,
            'by_status' => $metrics['by_status'],
            'by_source' => $this->groupCounts(
                $this->scoped(UserConsent::query(), $scope),
                'source'
            ),
            'trends' => [
                'labels' => $this->dateLabels($scope),
                'granted' => $this->dailyCounts(
                    UserConsent::query()->where('status', ConsentStatus::Granted->value),
                    $scope
                ),
                'withdrawn' => $this->dailyCounts(
                    UserConsent::query()->where('status', ConsentStatus::Withdrawn->value),
                    $scope
                ),
            ],
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    public function auditReport(array $scope): array
    {
        $metrics = $this->auditMetrics($scope);

        $recent = ActivityLog::query()
            ->where('log_name', 'compliance')
            ->whereBetween('created_at', [$scope['from'], $scope['to']])
            ->with('causer:id,uuid,full_name,email')
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'causer' => $log->causer ? [
                    'uuid' => $log->causer->uuid,
                    'full_name' => $log->causer->full_name,
                    'email' => $log->causer->email,
                ] : null,
                'created_at' => $log->created_at,
            ])
            ->all();

        return [
            'period' => [
                'from' => $scope['from']->toDateString(),
                'to' => $scope['to']->toDateString(),
            ],
            'summary' => $metrics,
            'by_event' => $metrics['by_event'],
            'recent' => $recent,
            'trends' => [
                'labels' => $this->dateLabels($scope),
                'events' => $this->dailyAuditCounts($scope),
            ],
        ];
    }

    /**
     * Flatten overview KPIs into exportable rows.
     *
     * @param  array<string, mixed>  $overview
     * @return list<array{0: string, 1: string|int|float}>
     */
    public function exportRows(array $overview): array
    {
        $kpis = $overview['kpis'] ?? [];
        $period = $overview['period'] ?? [];

        $rows = [
            ['Metric', 'Value'],
            ['Period From', $period['from'] ?? ''],
            ['Period To', $period['to'] ?? ''],
            ['Privacy Requests', $kpis['privacy_requests'] ?? 0],
            ['Privacy Requests Open', $kpis['privacy_requests_open'] ?? 0],
            ['Average Resolution Hours', $kpis['average_resolution_hours'] ?? 0],
            ['Compliance Cases', $kpis['compliance_cases'] ?? 0],
            ['Compliance Cases Open', $kpis['compliance_cases_open'] ?? 0],
            ['Average Risk Score', $kpis['risk_score'] ?? 0],
            ['Open Risks', $kpis['open_risks'] ?? 0],
            ['Closed Risks', $kpis['closed_risks'] ?? 0],
            ['Policy Updates', $kpis['policy_updates'] ?? 0],
            ['Consents Granted', $kpis['consent_granted'] ?? 0],
            ['Consents Withdrawn', $kpis['consent_withdrawn'] ?? 0],
            ['Data Breaches', $kpis['data_breaches'] ?? 0],
            ['Data Breaches Open', $kpis['data_breaches_open'] ?? 0],
            ['Audit Events', $kpis['audit_events'] ?? 0],
        ];

        return $rows;
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    private function privacyMetrics(array $scope): array
    {
        $query = $this->scoped(PrivacyRequest::query(), $scope);
        $byStatus = $this->groupCounts($query->clone(), 'status');

        $completed = $query->clone()
            ->where('status', PrivacyRequestStatus::Completed->value)
            ->whereNotNull('completed_at')
            ->get(['created_at', 'completed_at']);

        $avgHours = $completed->isEmpty()
            ? null
            : $completed->avg(function (PrivacyRequest $request): float {
                return max(0, $request->created_at->diffInMinutes($request->completed_at) / 60);
            });

        $open = $query->clone()
            ->whereIn('status', PrivacyRequestStatus::activeValues())
            ->count();

        return [
            'total' => $query->clone()->count(),
            'open' => $open,
            'completed' => (int) ($byStatus[PrivacyRequestStatus::Completed->value] ?? 0),
            'average_resolution_hours' => $avgHours !== null ? round((float) $avgHours, 1) : 0.0,
            'by_status' => $byStatus,
            'by_type' => $this->groupCounts($query->clone(), 'request_type'),
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    private function caseMetrics(array $scope): array
    {
        $query = $this->scoped(ComplianceCase::query(), $scope);
        $byStatus = $this->groupCounts($query->clone(), 'status');
        $openStatuses = [
            ComplianceCaseStatus::Open->value,
            ComplianceCaseStatus::InProgress->value,
            ComplianceCaseStatus::UnderReview->value,
            ComplianceCaseStatus::Pending->value,
        ];

        return [
            'total' => $query->clone()->count(),
            'open' => $query->clone()->whereIn('status', $openStatuses)->count(),
            'closed' => (int) ($byStatus[ComplianceCaseStatus::Closed->value] ?? 0)
                + (int) ($byStatus[ComplianceCaseStatus::Completed->value] ?? 0),
            'by_status' => $byStatus,
            'by_type' => $this->groupCounts($query->clone(), 'case_type'),
            'by_priority' => $this->groupCounts($query->clone(), 'priority'),
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    private function riskMetrics(array $scope): array
    {
        $base = RiskRegister::query()
            ->when($scope['company_id'], fn (Builder $q) => $q->where('company_id', $scope['company_id']));

        $periodCreated = $this->scoped(RiskRegister::query(), $scope);
        $open = $base->clone()->whereIn('status', RiskRegisterStatus::activeValues())->count();
        $closed = $base->clone()->where('status', RiskRegisterStatus::Closed->value)->count();
        $avgScore = $base->clone()
            ->whereIn('status', RiskRegisterStatus::activeValues())
            ->avg('risk_score');

        return [
            'total' => $base->clone()->count(),
            'created_in_period' => $periodCreated->count(),
            'open' => $open,
            'closed' => $closed,
            'average_score' => $avgScore !== null ? round((float) $avgScore, 1) : 0.0,
            'by_level' => $this->groupCounts($base->clone(), 'risk_level'),
            'by_status' => $this->groupCounts($base->clone(), 'status'),
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    private function policyMetrics(array $scope): array
    {
        $versions = PolicyVersion::query()
            ->whereBetween('created_at', [$scope['from'], $scope['to']])
            ->when($scope['company_id'], function (Builder $q) use ($scope): void {
                $q->whereHas('policy', fn (Builder $p) => $p->where('company_id', $scope['company_id']));
            });

        $policies = $this->scoped(PolicyDocument::query(), $scope);

        return [
            'updates' => $versions->count(),
            'documents' => $policies->clone()->count(),
            'published' => $policies->clone()->where('status', 'published')->count(),
            'by_status' => $this->groupCounts($policies->clone(), 'status'),
            'by_type' => $this->groupCounts($policies->clone(), 'policy_type'),
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    private function consentMetrics(array $scope): array
    {
        $query = $this->scoped(UserConsent::query(), $scope);
        $byStatus = $this->groupCounts($query->clone(), 'status');

        return [
            'total' => $query->clone()->count(),
            'granted' => (int) ($byStatus[ConsentStatus::Granted->value] ?? 0),
            'withdrawn' => (int) ($byStatus[ConsentStatus::Withdrawn->value] ?? 0),
            'pending' => (int) ($byStatus[ConsentStatus::Pending->value] ?? 0),
            'expired' => (int) ($byStatus[ConsentStatus::Expired->value] ?? 0),
            'by_status' => $byStatus,
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    private function breachMetrics(array $scope): array
    {
        $query = $this->scoped(DataBreach::query(), $scope);
        $byStatus = $this->groupCounts($query->clone(), 'status');
        $openStatuses = [
            DataBreachStatus::Reported->value,
            DataBreachStatus::Assessing->value,
            DataBreachStatus::Contained->value,
            DataBreachStatus::Recovering->value,
            DataBreachStatus::Notifying->value,
        ];

        return [
            'total' => $query->clone()->count(),
            'open' => $query->clone()->whereIn('status', $openStatuses)->count(),
            'closed' => (int) ($byStatus[DataBreachStatus::Closed->value] ?? 0),
            'by_status' => $byStatus,
            'by_severity' => $this->groupCounts($query->clone(), 'severity'),
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    private function auditMetrics(array $scope): array
    {
        $query = ActivityLog::query()
            ->where('log_name', 'compliance')
            ->whereBetween('created_at', [$scope['from'], $scope['to']]);

        $byEvent = $query->clone()
            ->selectRaw('event, COUNT(*) as aggregate')
            ->groupBy('event')
            ->pluck('aggregate', 'event')
            ->map(fn ($v) => (int) $v)
            ->all();

        return [
            'total' => $query->clone()->count(),
            'by_event' => $byEvent,
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return Builder<TModel>
     */
    private function scoped(Builder $query, array $scope, string $dateColumn = 'created_at'): Builder
    {
        return $query
            ->when($scope['company_id'], fn (Builder $q) => $q->where('company_id', $scope['company_id']))
            ->whereBetween($dateColumn, [$scope['from'], $scope['to']]);
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return array<string, int>
     */
    private function groupCounts(Builder $query, string $column): array
    {
        return $query
            ->selectRaw("{$column} as bucket, COUNT(*) as aggregate")
            ->groupBy($column)
            ->pluck('aggregate', 'bucket')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return list<string>
     */
    private function dateLabels(array $scope): array
    {
        $labels = [];
        foreach (CarbonPeriod::create($scope['from']->copy()->startOfDay(), '1 day', $scope['to']->copy()->startOfDay()) as $date) {
            $labels[] = $date->toDateString();
        }

        return $labels;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return list<int>
     */
    private function dailyCounts(Builder $query, array $scope, string $dateColumn = 'created_at'): array
    {
        $scoped = $query
            ->when(
                $scope['company_id'] && in_array('company_id', $query->getModel()->getFillable(), true),
                fn (Builder $q) => $q->where('company_id', $scope['company_id'])
            )
            ->whereBetween($dateColumn, [$scope['from'], $scope['to']]);

        /** @var Collection<string, int> $counts */
        $counts = $scoped
            ->selectRaw("DATE({$dateColumn}) as day, COUNT(*) as aggregate")
            ->groupBy('day')
            ->pluck('aggregate', 'day')
            ->map(fn ($v) => (int) $v);

        return array_map(
            fn (string $label) => (int) ($counts[$label] ?? 0),
            $this->dateLabels($scope)
        );
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return list<int>
     */
    private function dailyAuditCounts(array $scope): array
    {
        /** @var Collection<string, int> $counts */
        $counts = ActivityLog::query()
            ->where('log_name', 'compliance')
            ->whereBetween('created_at', [$scope['from'], $scope['to']])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day')
            ->map(fn ($v) => (int) $v);

        return array_map(
            fn (string $label) => (int) ($counts[$label] ?? 0),
            $this->dateLabels($scope)
        );
    }
}
