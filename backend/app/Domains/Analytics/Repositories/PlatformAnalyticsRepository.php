<?php

namespace App\Domains\Analytics\Repositories;

use App\Domains\Ai\Models\AiUsageLog;
use App\Domains\Automation\Enums\AutomationLogStatus;
use App\Domains\Automation\Models\AutomationLog;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Notifications\Enums\NotificationDeliveryStatus;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationLog;
use App\Domains\Workflows\Enums\WorkflowInstanceStatus;
use App\Domains\Workflows\Models\WorkflowInstance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlatformAnalyticsRepository
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
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

        return [
            'company_id' => $companyId,
            'from' => $from,
            'to' => $to,
            'days' => max(1, $from->diffInDays($to) + 1),
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    public function dashboard(array $scope): array
    {
        $notifications = $this->notificationReport($scope);
        $automation = $this->automationReport($scope);
        $workflows = $this->workflowReport($scope);
        $ai = $this->aiReport($scope);

        return [
            'period' => $this->periodMeta($scope),
            'kpis' => [
                'notifications_sent' => $notifications['sent'],
                'notifications_failed' => $notifications['failed'],
                'avg_delivery_seconds' => $notifications['avg_delivery_seconds'],
                'read_rate' => $notifications['read_rate'],
                'click_rate' => $notifications['click_rate'],
                'automation_executions' => $automation['total'],
                'automation_success_rate' => $automation['success_rate'],
                'workflow_success_rate' => $workflows['success_rate'],
                'workflow_failures' => $workflows['failures'],
                'avg_workflow_processing_seconds' => $workflows['avg_processing_seconds'],
                'ai_requests' => $ai['requests'],
                'ai_tokens' => $ai['tokens_in'] + $ai['tokens_out'],
            ],
            'notifications' => $notifications,
            'automation' => $automation,
            'workflows' => $workflows,
            'ai' => $ai,
            'charts' => [
                'notifications_daily' => [
                    'labels' => $this->dateLabels($scope),
                    'sent' => $this->dailyCounts(NotificationLog::query()->where('status', NotificationDeliveryStatus::Sent->value), $scope, 'sent_at'),
                    'failed' => $this->dailyCounts(NotificationLog::query()->where('status', NotificationDeliveryStatus::Failed->value), $scope, 'failed_at'),
                ],
                'automation_daily' => [
                    'labels' => $this->dateLabels($scope),
                    'success' => $this->dailyCounts(AutomationLog::query()->where('status', AutomationLogStatus::Success->value), $scope),
                    'failed' => $this->dailyCounts(AutomationLog::query()->where('status', AutomationLogStatus::Failed->value), $scope),
                ],
                'workflow_daily' => [
                    'labels' => $this->dateLabels($scope),
                    'completed' => $this->dailyCounts(
                        WorkflowInstance::query()->whereIn('status', [
                            WorkflowInstanceStatus::Approved->value,
                            WorkflowInstanceStatus::Completed->value,
                        ]),
                        $scope,
                        'completed_at'
                    ),
                    'failed' => $this->dailyCounts(
                        WorkflowInstance::query()->whereIn('status', [
                            WorkflowInstanceStatus::Rejected->value,
                            WorkflowInstanceStatus::TimedOut->value,
                            WorkflowInstanceStatus::Cancelled->value,
                        ]),
                        $scope,
                        'completed_at'
                    ),
                ],
                'ai_daily' => [
                    'labels' => $this->dateLabels($scope),
                    'requests' => $this->dailyCounts(AiUsageLog::query(), $scope),
                ],
            ],
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    public function notificationReport(array $scope): array
    {
        $logs = $this->scoped(NotificationLog::query(), $scope, 'created_at');
        $notifications = $this->scoped(Notification::query(), $scope, 'created_at');

        $sent = (clone $logs)->where('status', NotificationDeliveryStatus::Sent->value)->count();
        $failed = (clone $logs)->where('status', NotificationDeliveryStatus::Failed->value)->count();
        $queued = (clone $logs)->where('status', NotificationDeliveryStatus::Queued->value)->count();
        $totalDelivery = $sent + $failed;

        $avgDelivery = $this->averageDurationSeconds(
            (clone $logs)
                ->where('status', NotificationDeliveryStatus::Sent->value)
                ->whereNotNull('queued_at')
                ->whereNotNull('sent_at'),
            'queued_at',
            'sent_at'
        );

        $platformSent = (clone $notifications)->where('status', NotificationStatus::Sent->value)->count();
        $platformFailed = (clone $notifications)->where('status', NotificationStatus::Failed->value)->count();
        $readable = (clone $notifications)->whereNotNull('sent_at')->count();
        $read = (clone $notifications)->whereNotNull('read_at')->count();
        $clicked = (clone $notifications)->whereNotNull('clicked_at')->count();

        $byChannel = (clone $logs)
            ->select('channel', DB::raw('COUNT(*) as total'))
            ->groupBy('channel')
            ->pluck('total', 'channel')
            ->map(fn ($v) => (int) $v)
            ->all();

        $byStatus = (clone $logs)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [
                ($row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status) => (int) $row->total,
            ])
            ->all();

        $byEvent = (clone $logs)
            ->select('event_key', DB::raw('COUNT(*) as total'))
            ->groupBy('event_key')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'event_key' => $row->event_key instanceof \BackedEnum ? $row->event_key->value : (string) $row->event_key,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        return [
            'period' => $this->periodMeta($scope),
            'sent' => $sent,
            'failed' => $failed,
            'queued' => $queued,
            'platform_sent' => $platformSent,
            'platform_failed' => $platformFailed,
            'delivery_success_rate' => $this->rate($sent, $totalDelivery),
            'avg_delivery_seconds' => round((float) ($avgDelivery ?? 0), 2),
            'readable' => $readable,
            'read' => $read,
            'read_rate' => $this->rate($read, $readable),
            'clicked' => $clicked,
            'click_rate' => $this->rate($clicked, max($readable, $sent)),
            'by_channel' => $this->normalizeKeyed($byChannel),
            'by_status' => $byStatus,
            'top_events' => $byEvent,
            'trends' => [
                'labels' => $this->dateLabels($scope),
                'sent' => $this->dailyCounts(NotificationLog::query()->where('status', NotificationDeliveryStatus::Sent->value), $scope, 'sent_at'),
                'failed' => $this->dailyCounts(NotificationLog::query()->where('status', NotificationDeliveryStatus::Failed->value), $scope, 'failed_at'),
                'reads' => $this->dailyCounts(Notification::query()->whereNotNull('read_at'), $scope, 'read_at'),
                'clicks' => $this->dailyCounts(Notification::query()->whereNotNull('clicked_at'), $scope, 'clicked_at'),
            ],
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    public function automationReport(array $scope): array
    {
        $query = $this->scopedAutomation($scope);
        $total = (clone $query)->count();
        $success = (clone $query)->where('status', AutomationLogStatus::Success->value)->count();
        $failed = (clone $query)->where('status', AutomationLogStatus::Failed->value)->count();
        $skipped = (clone $query)->where('status', AutomationLogStatus::Skipped->value)->count();
        $running = (clone $query)->where('status', AutomationLogStatus::Running->value)->count();

        $avgSeconds = $this->averageDurationSeconds(
            (clone $query)
                ->whereNotNull('started_at')
                ->whereNotNull('finished_at'),
            'started_at',
            'finished_at'
        );

        $byStatus = (clone $query)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [
                ($row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status) => (int) $row->total,
            ])
            ->all();

        $byTrigger = (clone $query)
            ->select('trigger_type', DB::raw('COUNT(*) as total'))
            ->groupBy('trigger_type')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(fn ($row) => [
                ($row->trigger_type instanceof \BackedEnum ? $row->trigger_type->value : (string) $row->trigger_type) => (int) $row->total,
            ])
            ->all();

        $byRule = (clone $query)
            ->select('automation_rule_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'))
            ->groupBy('automation_rule_id')
            ->orderByDesc('total')
            ->limit(10)
            ->with(['rule:id,uuid,name'])
            ->get()
            ->map(fn ($row) => [
                'rule_id' => $row->automation_rule_id,
                'rule_uuid' => $row->rule?->uuid,
                'rule_name' => $row->rule?->name ?? 'Rule #'.$row->automation_rule_id,
                'total' => (int) $row->total,
                'success' => (int) $row->success_count,
                'success_rate' => $this->rate((int) $row->success_count, (int) $row->total),
            ])
            ->values()
            ->all();

        return [
            'period' => $this->periodMeta($scope),
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'skipped' => $skipped,
            'running' => $running,
            'success_rate' => $this->rate($success, max(1, $success + $failed)),
            'avg_processing_seconds' => round((float) ($avgSeconds ?? 0), 2),
            'by_status' => $byStatus,
            'by_trigger' => $byTrigger,
            'top_rules' => $byRule,
            'trends' => [
                'labels' => $this->dateLabels($scope),
                'executions' => $this->dailyCounts(AutomationLog::query(), $scope),
                'success' => $this->dailyCounts(AutomationLog::query()->where('status', AutomationLogStatus::Success->value), $scope),
                'failed' => $this->dailyCounts(AutomationLog::query()->where('status', AutomationLogStatus::Failed->value), $scope),
            ],
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    public function workflowReport(array $scope): array
    {
        $query = $this->scoped(WorkflowInstance::query(), $scope, 'created_at');

        $total = (clone $query)->count();
        $successStatuses = [
            WorkflowInstanceStatus::Approved->value,
            WorkflowInstanceStatus::Completed->value,
        ];
        $failureStatuses = [
            WorkflowInstanceStatus::Rejected->value,
            WorkflowInstanceStatus::TimedOut->value,
            WorkflowInstanceStatus::Cancelled->value,
        ];

        $success = (clone $query)->whereIn('status', $successStatuses)->count();
        $failures = (clone $query)->whereIn('status', $failureStatuses)->count();
        $inProgress = (clone $query)->whereIn('status', [
            WorkflowInstanceStatus::Pending->value,
            WorkflowInstanceStatus::InProgress->value,
        ])->count();

        $avgSeconds = $this->averageDurationSeconds(
            (clone $query)
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->whereIn('status', array_merge($successStatuses, $failureStatuses)),
            'started_at',
            'completed_at'
        );

        $byStatus = (clone $query)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [
                ($row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status) => (int) $row->total,
            ])
            ->all();

        $byWorkflow = (clone $query)
            ->select('workflow_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status IN ("approved","completed") THEN 1 ELSE 0 END) as success_count'))
            ->groupBy('workflow_id')
            ->orderByDesc('total')
            ->limit(10)
            ->with(['workflow:id,uuid,name'])
            ->get()
            ->map(fn ($row) => [
                'workflow_id' => $row->workflow_id,
                'workflow_uuid' => $row->workflow?->uuid,
                'workflow_name' => $row->workflow?->name ?? 'Workflow #'.$row->workflow_id,
                'total' => (int) $row->total,
                'success' => (int) $row->success_count,
                'success_rate' => $this->rate((int) $row->success_count, (int) $row->total),
            ])
            ->values()
            ->all();

        return [
            'period' => $this->periodMeta($scope),
            'total' => $total,
            'success' => $success,
            'failures' => $failures,
            'in_progress' => $inProgress,
            'success_rate' => $this->rate($success, max(1, $success + $failures)),
            'avg_processing_seconds' => round((float) ($avgSeconds ?? 0), 2),
            'by_status' => $byStatus,
            'top_workflows' => $byWorkflow,
            'trends' => [
                'labels' => $this->dateLabels($scope),
                'created' => $this->dailyCounts(WorkflowInstance::query(), $scope),
                'completed' => $this->dailyCounts(
                    WorkflowInstance::query()->whereIn('status', $successStatuses),
                    $scope,
                    'completed_at'
                ),
                'failed' => $this->dailyCounts(
                    WorkflowInstance::query()->whereIn('status', $failureStatuses),
                    $scope,
                    'completed_at'
                ),
            ],
        ];
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array<string, mixed>
     */
    public function aiReport(array $scope): array
    {
        $query = $this->scoped(AiUsageLog::query(), $scope, 'created_at');

        $totals = (clone $query)
            ->selectRaw('COUNT(*) as requests')
            ->selectRaw('SUM(tokens_in) as tokens_in')
            ->selectRaw('SUM(tokens_out) as tokens_out')
            ->selectRaw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count')
            ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count')
            ->selectRaw('AVG(latency_ms) as avg_latency_ms')
            ->selectRaw('SUM(cost_estimate) as cost_estimate')
            ->first();

        $byFeature = (clone $query)
            ->select('feature', DB::raw('COUNT(*) as total'), DB::raw('SUM(tokens_in + tokens_out) as tokens'))
            ->groupBy('feature')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'feature' => $row->feature instanceof \BackedEnum ? $row->feature->value : (string) $row->feature,
                'total' => (int) $row->total,
                'tokens' => (int) $row->tokens,
            ])
            ->values()
            ->all();

        $byDriver = (clone $query)
            ->select('driver', DB::raw('COUNT(*) as total'), DB::raw('SUM(tokens_in + tokens_out) as tokens'))
            ->groupBy('driver')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'driver' => $row->driver,
                'total' => (int) $row->total,
                'tokens' => (int) $row->tokens,
            ])
            ->values()
            ->all();

        return [
            'period' => $this->periodMeta($scope),
            'requests' => (int) ($totals->requests ?? 0),
            'tokens_in' => (int) ($totals->tokens_in ?? 0),
            'tokens_out' => (int) ($totals->tokens_out ?? 0),
            'success_count' => (int) ($totals->success_count ?? 0),
            'failed_count' => (int) ($totals->failed_count ?? 0),
            'avg_latency_ms' => round((float) ($totals->avg_latency_ms ?? 0), 2),
            'cost_estimate' => round((float) ($totals->cost_estimate ?? 0), 6),
            'by_feature' => $byFeature,
            'by_driver' => $byDriver,
            'trends' => [
                'labels' => $this->dateLabels($scope),
                'requests' => $this->dailyCounts(AiUsageLog::query(), $scope),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<int, string|int|float>>
     */
    public function exportRows(string $report, array $payload): array
    {
        $rows = [
            ['Report', $report],
            ['Period From', $payload['period']['from'] ?? ''],
            ['Period To', $payload['period']['to'] ?? ''],
            ['Section', 'Metric', 'Value'],
        ];

        $kpis = $payload['kpis'] ?? null;
        if (is_array($kpis)) {
            foreach ($kpis as $key => $value) {
                $rows[] = ['kpis', (string) $key, is_scalar($value) ? $value : json_encode($value)];
            }
        }

        foreach (['notifications', 'automation', 'workflows', 'ai'] as $section) {
            if (! isset($payload[$section]) || ! is_array($payload[$section])) {
                continue;
            }
            foreach ($payload[$section] as $key => $value) {
                if (in_array($key, ['period', 'trends', 'top_events', 'top_rules', 'top_workflows', 'by_feature', 'by_driver'], true)) {
                    continue;
                }
                if (is_scalar($value) || $value === null) {
                    $rows[] = [$section, (string) $key, $value ?? ''];
                } elseif (is_array($value) && ! array_is_list($value)) {
                    foreach ($value as $childKey => $childValue) {
                        if (is_scalar($childValue) || $childValue === null) {
                            $rows[] = [$section.'.'.$key, (string) $childKey, $childValue ?? ''];
                        }
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * @param  array{company_id: ?int, from: Carbon, to: Carbon, days: int}  $scope
     * @return array{from: string, to: string, days: int}
     */
    protected function periodMeta(array $scope): array
    {
        return [
            'from' => $scope['from']->toDateString(),
            'to' => $scope['to']->toDateString(),
            'days' => $scope['days'],
        ];
    }

    protected function rate(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 2);
    }

    /**
     * @param  array<string|int, int>  $data
     * @return array<string, int>
     */
    protected function normalizeKeyed(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            $label = $key instanceof \BackedEnum ? $key->value : (string) $key;
            $normalized[$label !== '' ? $label : 'unknown'] = (int) $value;
        }

        return $normalized;
    }

    protected function scoped(Builder $query, array $scope, string $dateColumn = 'created_at'): Builder
    {
        $query->whereBetween($dateColumn, [$scope['from'], $scope['to']]);
        if ($scope['company_id'] !== null && $this->hasColumn($query, 'company_id')) {
            $query->where('company_id', $scope['company_id']);
        }

        return $query;
    }

    protected function scopedAutomation(array $scope): Builder
    {
        $query = AutomationLog::query()->whereBetween('created_at', [$scope['from'], $scope['to']]);
        if ($scope['company_id'] !== null) {
            $query->whereHas('rule', fn (Builder $builder) => $builder->where('company_id', $scope['company_id']));
        }

        return $query;
    }

    protected function hasColumn(Builder $query, string $column): bool
    {
        return Schema::hasColumn($query->getModel()->getTable(), $column);
    }

    /**
     * Cross-driver average duration in seconds between two datetime columns.
     */
    protected function averageDurationSeconds(Builder $query, string $startColumn, string $endColumn): float
    {
        $driver = $query->getConnection()->getDriverName();
        $expression = $driver === 'sqlite'
            ? "AVG((julianday({$endColumn}) - julianday({$startColumn})) * 86400)"
            : "AVG(TIMESTAMPDIFF(SECOND, {$startColumn}, {$endColumn}))";

        return (float) ($query->selectRaw("{$expression} as avg_seconds")->value('avg_seconds') ?? 0);
    }

    /**
     * @return list<string>
     */
    protected function dateLabels(array $scope): array
    {
        return collect(CarbonPeriod::create($scope['from']->copy()->startOfDay(), $scope['to']->copy()->startOfDay()))
            ->map(fn (Carbon $day) => $day->toDateString())
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    protected function dailyCounts(Builder $query, array $scope, string $dateColumn = 'created_at'): array
    {
        $clone = clone $query;
        if ($scope['company_id'] !== null && $this->hasColumn($clone, 'company_id')) {
            $clone->where('company_id', $scope['company_id']);
        }

        $rows = $clone
            ->whereBetween($dateColumn, [$scope['from'], $scope['to']])
            ->select(DB::raw("DATE({$dateColumn}) as day"), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();

        return collect($this->dateLabels($scope))
            ->map(fn (string $day) => (int) ($rows[$day] ?? 0))
            ->values()
            ->all();
    }
}
