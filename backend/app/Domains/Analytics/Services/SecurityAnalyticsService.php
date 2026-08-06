<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Models\SecurityAnalyticsSnapshot;
use App\Domains\Analytics\Repositories\SecurityAnalyticsSnapshotRepository;
use App\Domains\Audit\Models\ApiLog;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\PrivacyRequestType;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Content\Models\CmsApiKey;
use App\Domains\Users\Models\UserLoginHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class SecurityAnalyticsService
{
    public function __construct(
        private readonly SecurityAnalyticsSnapshotRepository $snapshotRepository,
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

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'kpis' => $this->kpiPayload($metrics),
            'risk' => [
                'score' => $metrics['risk_score'],
                'level' => $this->riskLevel($metrics['risk_score']),
                'indicators' => $this->buildRiskIndicators($metrics),
            ],
            'charts' => [
                'logins_success' => $this->seriesFromHistory($history, 'logins_success'),
                'logins_failed' => $this->seriesFromHistory($history, 'logins_failed'),
                'permission_changes' => $this->seriesFromHistory($history, 'permission_changes'),
                'role_changes' => $this->seriesFromHistory($history, 'role_changes'),
                'gdpr_requests' => $this->seriesFromHistory($history, 'gdpr_requests'),
                'security_events' => $this->seriesFromHistory($history, 'security_events'),
                'risk_score' => $this->seriesFromHistory($history, 'risk_score'),
            ],
            'heatmap' => $this->activityHeatmap($companyId, $range['from'], $range['to']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function auditDashboard(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);
        $metrics = $this->aggregateMetrics($companyId, $range['from'], $range['to']);
        $history = $this->ensureHistory($companyId, $range['from'], $range['to']);

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'kpis' => [
                'logins_success' => $metrics['logins_success'],
                'logins_failed' => $metrics['logins_failed'],
                'permission_changes' => $metrics['permission_changes'],
                'role_changes' => $metrics['role_changes'],
                'data_exports' => $metrics['data_exports'],
                'data_deletions' => $metrics['data_deletions'],
                'audit_actions' => $metrics['audit_actions'],
            ],
            'charts' => [
                'logins_success' => $this->seriesFromHistory($history, 'logins_success'),
                'logins_failed' => $this->seriesFromHistory($history, 'logins_failed'),
                'permission_changes' => $this->seriesFromHistory($history, 'permission_changes'),
                'role_changes' => $this->seriesFromHistory($history, 'role_changes'),
                'data_exports' => $this->seriesFromHistory($history, 'data_exports'),
                'data_deletions' => $this->seriesFromHistory($history, 'data_deletions'),
            ],
            'recent_role_events' => $this->recentRoleEvents(15),
            'recent_audit_actions' => $this->recentAuditActions($companyId, 15),
            'heatmap' => $this->activityHeatmap($companyId, $range['from'], $range['to']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function securityDashboard(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);
        $metrics = $this->aggregateMetrics($companyId, $range['from'], $range['to']);
        $history = $this->ensureHistory($companyId, $range['from'], $range['to']);

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'kpis' => [
                'logins_failed' => $metrics['logins_failed'],
                'security_events' => $metrics['security_events'],
                'api_errors' => $metrics['api_errors'],
                'api_key_uses' => $metrics['api_key_uses'],
                'gdpr_requests' => $metrics['gdpr_requests'],
                'risk_score' => $metrics['risk_score'],
            ],
            'risk' => [
                'score' => $metrics['risk_score'],
                'level' => $this->riskLevel($metrics['risk_score']),
                'indicators' => $this->buildRiskIndicators($metrics),
            ],
            'charts' => [
                'logins_failed' => $this->seriesFromHistory($history, 'logins_failed'),
                'security_events' => $this->seriesFromHistory($history, 'security_events'),
                'api_errors' => $this->seriesFromHistory($history, 'api_errors'),
                'risk_score' => $this->seriesFromHistory($history, 'risk_score'),
            ],
            'failed_login_ips' => $this->topFailedLoginIps($range['from'], $range['to']),
            'api_keys' => $this->apiKeyUsageSummary(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function threatTimeline(?string $company = null, ?string $from = null, ?string $to = null, int $limit = 75): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 14);
        $items = collect();

        $failedLogins = UserLoginHistory::query()
            ->where('status', 'failed')
            ->whereBetween('logged_in_at', [$range['from'], $range['to']])
            ->latest('logged_in_at')
            ->limit($limit)
            ->get(['uuid', 'user_id', 'ip_address', 'browser', 'logged_in_at']);

        foreach ($failedLogins as $row) {
            $items->push([
                'kind' => 'failed_login',
                'severity' => 'warning',
                'title' => 'Failed login',
                'message' => sprintf('Failed login from %s (%s)', $row->ip_address ?: 'unknown IP', $row->browser ?: 'unknown browser'),
                'occurred_at' => optional($row->logged_in_at)?->toIso8601String(),
                'context' => ['uuid' => $row->uuid, 'user_id' => $row->user_id],
            ]);
        }

        if (Schema::hasTable('activity_log')) {
            Activity::query()
                ->where('log_name', 'roles')
                ->whereBetween('created_at', [$range['from'], $range['to']])
                ->latest()
                ->limit($limit)
                ->get()
                ->each(function (Activity $activity) use ($items): void {
                    $event = data_get($activity->properties, 'event', $activity->event ?: 'role_change');
                    $items->push([
                        'kind' => 'permission_role',
                        'severity' => str_contains((string) $event, 'removed') || str_contains((string) $event, 'deleted') ? 'high' : 'info',
                        'title' => Str::headline((string) $event),
                        'message' => $activity->description,
                        'occurred_at' => optional($activity->created_at)?->toIso8601String(),
                        'context' => [
                            'id' => $activity->id,
                            'causer_id' => $activity->causer_id,
                            'event' => $event,
                        ],
                    ]);
                });
        }

        if (Schema::hasTable('privacy_requests')) {
            PrivacyRequest::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereBetween('created_at', [$range['from'], $range['to']])
                ->latest()
                ->limit($limit)
                ->get(['uuid', 'request_type', 'status', 'created_at'])
                ->each(function (PrivacyRequest $request) use ($items): void {
                    $type = $request->request_type instanceof \BackedEnum
                        ? $request->request_type->value
                        : (string) $request->request_type;
                    $items->push([
                        'kind' => 'gdpr',
                        'severity' => $type === PrivacyRequestType::DataDeletion->value ? 'high' : 'info',
                        'title' => 'GDPR '.$type,
                        'message' => sprintf('Privacy request %s (%s)', $type, $request->status instanceof \BackedEnum ? $request->status->value : $request->status),
                        'occurred_at' => optional($request->created_at)?->toIso8601String(),
                        'context' => ['uuid' => $request->uuid],
                    ]);
                });
        }

        if (Schema::hasTable('api_logs')) {
            ApiLog::query()
                ->where('response_code', '>=', 400)
                ->whereBetween('created_at', [$range['from'], $range['to']])
                ->latest()
                ->limit(25)
                ->get(['uuid', 'endpoint', 'method', 'response_code', 'created_at'])
                ->each(function (ApiLog $log) use ($items): void {
                    $items->push([
                        'kind' => 'api_error',
                        'severity' => $log->response_code >= 500 ? 'critical' : 'warning',
                        'title' => 'API '.$log->response_code,
                        'message' => sprintf('%s %s', $log->method, $log->endpoint),
                        'occurred_at' => optional($log->created_at)?->toIso8601String(),
                        'context' => ['uuid' => $log->uuid],
                    ]);
                });
        }

        $sorted = $items->sortByDesc('occurred_at')->values()->take($limit)->all();

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'items' => $sorted,
            'meta' => ['total' => count($sorted), 'limit' => $limit],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function riskIndicators(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);
        $metrics = $this->aggregateMetrics($companyId, $range['from'], $range['to']);

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'score' => $metrics['risk_score'],
            'level' => $this->riskLevel($metrics['risk_score']),
            'indicators' => $this->buildRiskIndicators($metrics),
            'kpis' => $this->kpiPayload($metrics),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function activityHeatmapEndpoint(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $companyId = $this->resolveCompanyId($company);
        $range = $this->resolveRange($from, $to, 30);

        return [
            'period' => [
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'heatmap' => $this->activityHeatmap($companyId, $range['from'], $range['to']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function exportReport(?string $company = null, ?string $from = null, ?string $to = null): array
    {
        $overview = $this->overview($company, $from, $to);
        $timeline = $this->threatTimeline($company, $from, $to, 200);

        return [
            'generated_at' => now()->toIso8601String(),
            'overview' => $overview,
            'timeline' => $timeline['items'],
            'export_ready' => true,
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
        // Day-scoped counters for snapshot row
        $dayMetrics = $this->dayScopedMetrics($companyId, $day);
        $payload = array_merge($metrics, $dayMetrics);
        $payload['risk_score'] = $this->calculateRiskScore($payload);
        $snapshot = $this->persistSnapshot($companyId, $day, $payload);

        return [
            'snapshot' => [
                'uuid' => $snapshot->uuid,
                'snapshot_date' => optional($snapshot->snapshot_date)->toDateString(),
                'risk_score' => $snapshot->risk_score,
                'logins_failed' => $snapshot->logins_failed,
                'computed_at' => optional($snapshot->computed_at)?->toIso8601String(),
            ],
            'kpis' => $this->kpiPayload($payload),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function aggregateMetrics(?int $companyId, Carbon $from, Carbon $to): array
    {
        $loginsSuccess = UserLoginHistory::query()
            ->where('status', 'success')
            ->whereBetween('logged_in_at', [$from, $to])
            ->count();

        $loginsFailed = UserLoginHistory::query()
            ->where('status', 'failed')
            ->whereBetween('logged_in_at', [$from, $to])
            ->count();

        $permissionChanges = 0;
        $roleChanges = 0;
        if (Schema::hasTable('activity_log')) {
            $permissionChanges = Activity::query()
                ->where('log_name', 'roles')
                ->whereBetween('created_at', [$from, $to])
                ->where(function ($q): void {
                    $q->where('properties->event', 'like', 'permission_%')
                        ->orWhere('description', 'like', '%permission%');
                })
                ->count();

            $roleChanges = Activity::query()
                ->where('log_name', 'roles')
                ->whereBetween('created_at', [$from, $to])
                ->where(function ($q): void {
                    $q->where('properties->event', 'like', 'role_%')
                        ->orWhere('properties->event', 'like', 'user_role_%')
                        ->orWhere('description', 'like', '%role%');
                })
                ->count();
        }

        $dataExports = 0;
        $dataDeletions = 0;
        $gdprRequests = 0;
        if (Schema::hasTable('privacy_requests')) {
            $gdprQuery = PrivacyRequest::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereBetween('created_at', [$from, $to]);
            $gdprRequests = (clone $gdprQuery)->count();
            $dataExports = (clone $gdprQuery)->whereIn('request_type', [
                PrivacyRequestType::DataExport->value,
                PrivacyRequestType::AccessRequest->value,
                PrivacyRequestType::DataPortability->value,
            ])->count();
            $dataDeletions = (clone $gdprQuery)->where('request_type', PrivacyRequestType::DataDeletion->value)->count();
        }

        $auditActions = 0;
        $auditDeletes = 0;
        if (Schema::hasTable('audit_logs')) {
            $auditQuery = AuditLog::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereBetween('created_at', [$from, $to]);
            $auditActions = (clone $auditQuery)->count();
            $auditDeletes = (clone $auditQuery)->where(function ($q): void {
                $q->where('action', 'like', '%delete%')
                    ->orWhere('action', 'deleted')
                    ->orWhere('action', 'force_deleted');
            })->count();
            $dataDeletions += $auditDeletes;
        }

        $apiErrors = 0;
        if (Schema::hasTable('api_logs')) {
            $apiErrors = ApiLog::query()
                ->where('response_code', '>=', 400)
                ->whereBetween('created_at', [$from, $to])
                ->count();
        }

        $apiKeyUses = 0;
        if (Schema::hasTable('cms_api_keys')) {
            $apiKeyUses = CmsApiKey::query()
                ->whereNotNull('last_used_at')
                ->whereBetween('last_used_at', [$from, $to])
                ->count();
        }
        if (Schema::hasTable('personal_access_tokens')) {
            $apiKeyUses += (int) DB::table('personal_access_tokens')
                ->whereNotNull('last_used_at')
                ->whereBetween('last_used_at', [$from, $to])
                ->count();
        }

        $securityEvents = $loginsFailed + $permissionChanges + $roleChanges + $apiErrors + $dataDeletions;

        $metrics = [
            'logins_success' => $loginsSuccess,
            'logins_failed' => $loginsFailed,
            'permission_changes' => $permissionChanges,
            'role_changes' => $roleChanges,
            'data_exports' => $dataExports,
            'data_deletions' => $dataDeletions,
            'gdpr_requests' => $gdprRequests,
            'security_events' => $securityEvents,
            'api_key_uses' => $apiKeyUses,
            'api_errors' => $apiErrors,
            'audit_actions' => $auditActions,
        ];
        $metrics['risk_score'] = $this->calculateRiskScore($metrics);

        return $metrics;
    }

    /**
     * @return array<string, int>
     */
    protected function dayScopedMetrics(?int $companyId, Carbon $day): array
    {
        $from = $day->copy()->startOfDay();
        $to = $day->copy()->endOfDay();

        return [
            'logins_success' => UserLoginHistory::query()->where('status', 'success')->whereBetween('logged_in_at', [$from, $to])->count(),
            'logins_failed' => UserLoginHistory::query()->where('status', 'failed')->whereBetween('logged_in_at', [$from, $to])->count(),
            'permission_changes' => Schema::hasTable('activity_log')
                ? Activity::query()->where('log_name', 'roles')->whereBetween('created_at', [$from, $to])
                    ->where(fn ($q) => $q->where('properties->event', 'like', 'permission_%')->orWhere('description', 'like', '%permission%'))
                    ->count()
                : 0,
            'role_changes' => Schema::hasTable('activity_log')
                ? Activity::query()->where('log_name', 'roles')->whereBetween('created_at', [$from, $to])
                    ->where(fn ($q) => $q->where('properties->event', 'like', 'role_%')->orWhere('properties->event', 'like', 'user_role_%'))
                    ->count()
                : 0,
            'data_exports' => Schema::hasTable('privacy_requests')
                ? PrivacyRequest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereBetween('created_at', [$from, $to])
                    ->whereIn('request_type', [
                        PrivacyRequestType::DataExport->value,
                        PrivacyRequestType::AccessRequest->value,
                        PrivacyRequestType::DataPortability->value,
                    ])->count()
                : 0,
            'data_deletions' => Schema::hasTable('privacy_requests')
                ? PrivacyRequest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereBetween('created_at', [$from, $to])
                    ->where('request_type', PrivacyRequestType::DataDeletion->value)->count()
                : 0,
            'gdpr_requests' => Schema::hasTable('privacy_requests')
                ? PrivacyRequest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereBetween('created_at', [$from, $to])->count()
                : 0,
            'api_errors' => Schema::hasTable('api_logs')
                ? ApiLog::query()->where('response_code', '>=', 400)->whereBetween('created_at', [$from, $to])->count()
                : 0,
            'api_key_uses' => Schema::hasTable('cms_api_keys')
                ? CmsApiKey::query()->whereNotNull('last_used_at')->whereBetween('last_used_at', [$from, $to])->count()
                : 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    protected function calculateRiskScore(array $metrics): int
    {
        $score = 0;
        $score += min(40, (int) ($metrics['logins_failed'] ?? 0) * 2);
        $score += min(20, (int) ($metrics['permission_changes'] ?? 0) * 2);
        $score += min(15, (int) ($metrics['role_changes'] ?? 0) * 2);
        $score += min(15, (int) ($metrics['data_deletions'] ?? 0) * 3);
        $score += min(10, (int) (($metrics['api_errors'] ?? 0) / 5));

        return (int) min(100, $score);
    }

    protected function riskLevel(int $score): string
    {
        return match (true) {
            $score >= 70 => 'critical',
            $score >= 45 => 'high',
            $score >= 25 => 'medium',
            default => 'low',
        };
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return list<array{key: string, label: string, value: int|float, severity: string}>
     */
    protected function buildRiskIndicators(array $metrics): array
    {
        return [
            [
                'key' => 'failed_logins',
                'label' => 'Failed logins',
                'value' => (int) $metrics['logins_failed'],
                'severity' => $metrics['logins_failed'] >= 20 ? 'critical' : ($metrics['logins_failed'] >= 5 ? 'warning' : 'ok'),
            ],
            [
                'key' => 'permission_changes',
                'label' => 'Permission changes',
                'value' => (int) $metrics['permission_changes'],
                'severity' => $metrics['permission_changes'] >= 10 ? 'warning' : 'ok',
            ],
            [
                'key' => 'role_changes',
                'label' => 'Role changes',
                'value' => (int) $metrics['role_changes'],
                'severity' => $metrics['role_changes'] >= 10 ? 'warning' : 'ok',
            ],
            [
                'key' => 'data_deletions',
                'label' => 'Data deletions',
                'value' => (int) $metrics['data_deletions'],
                'severity' => $metrics['data_deletions'] >= 5 ? 'high' : 'ok',
            ],
            [
                'key' => 'api_errors',
                'label' => 'API errors',
                'value' => (int) $metrics['api_errors'],
                'severity' => $metrics['api_errors'] >= 50 ? 'warning' : 'ok',
            ],
            [
                'key' => 'gdpr_requests',
                'label' => 'GDPR requests',
                'value' => (int) $metrics['gdpr_requests'],
                'severity' => 'info',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array<string, mixed>
     */
    protected function kpiPayload(array $metrics): array
    {
        return [
            'logins_success' => $metrics['logins_success'],
            'logins_failed' => $metrics['logins_failed'],
            'permission_changes' => $metrics['permission_changes'],
            'role_changes' => $metrics['role_changes'],
            'data_exports' => $metrics['data_exports'],
            'data_deletions' => $metrics['data_deletions'],
            'gdpr_requests' => $metrics['gdpr_requests'],
            'security_events' => $metrics['security_events'],
            'api_key_uses' => $metrics['api_key_uses'],
            'api_errors' => $metrics['api_errors'],
            'risk_score' => $metrics['risk_score'],
        ];
    }

    /**
     * @return Collection<int, SecurityAnalyticsSnapshot>
     */
    protected function ensureHistory(?int $companyId, Carbon $from, Carbon $to): Collection
    {
        $existing = $this->snapshotRepository->history($companyId, $from, $to)
            ->keyBy(fn (SecurityAnalyticsSnapshot $row) => optional($row->snapshot_date)->toDateString());

        $today = now()->startOfDay();
        if ($today->gte($from->copy()->startOfDay()) && $today->lte($to->copy()->endOfDay())) {
            $dayMetrics = $this->dayScopedMetrics($companyId, $today);
            $period = $this->aggregateMetrics($companyId, $from, $to);
            $payload = array_merge($period, $dayMetrics);
            $payload['security_events'] = ($dayMetrics['logins_failed'] ?? 0)
                + ($dayMetrics['permission_changes'] ?? 0)
                + ($dayMetrics['role_changes'] ?? 0)
                + ($dayMetrics['api_errors'] ?? 0)
                + ($dayMetrics['data_deletions'] ?? 0);
            $payload['risk_score'] = $this->calculateRiskScore($payload);
            $existing->put($today->toDateString(), $this->persistSnapshot($companyId, $today, $payload));
        }

        $synthetic = collect();
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
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

    protected function makeSyntheticSnapshot(?int $companyId, Carbon $day): SecurityAnalyticsSnapshot
    {
        $metrics = $this->dayScopedMetrics($companyId, $day);
        $metrics['security_events'] = ($metrics['logins_failed'] ?? 0)
            + ($metrics['permission_changes'] ?? 0)
            + ($metrics['role_changes'] ?? 0)
            + ($metrics['api_errors'] ?? 0)
            + ($metrics['data_deletions'] ?? 0);
        $metrics['risk_score'] = $this->calculateRiskScore($metrics);

        $snapshot = new SecurityAnalyticsSnapshot(array_merge($metrics, [
            'company_id' => $companyId,
            'snapshot_date' => $day->toDateString(),
            'computed_at' => now(),
        ]));
        $snapshot->uuid = (string) Str::uuid();

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    protected function persistSnapshot(?int $companyId, Carbon $date, array $metrics): SecurityAnalyticsSnapshot
    {
        return $this->snapshotRepository->upsertForDate($companyId, $date, [
            'logins_success' => $metrics['logins_success'] ?? 0,
            'logins_failed' => $metrics['logins_failed'] ?? 0,
            'permission_changes' => $metrics['permission_changes'] ?? 0,
            'role_changes' => $metrics['role_changes'] ?? 0,
            'data_exports' => $metrics['data_exports'] ?? 0,
            'data_deletions' => $metrics['data_deletions'] ?? 0,
            'gdpr_requests' => $metrics['gdpr_requests'] ?? 0,
            'security_events' => $metrics['security_events'] ?? 0,
            'api_key_uses' => $metrics['api_key_uses'] ?? 0,
            'api_errors' => $metrics['api_errors'] ?? 0,
            'risk_score' => $metrics['risk_score'] ?? 0,
            'metrics' => [
                'audit_actions' => $metrics['audit_actions'] ?? 0,
            ],
        ]);
    }

    /**
     * @param  Collection<int, SecurityAnalyticsSnapshot>  $history
     * @return list<array{date: string, value: int}>
     */
    protected function seriesFromHistory(Collection $history, string $field): array
    {
        return $history->map(fn (SecurityAnalyticsSnapshot $row): array => [
            'date' => optional($row->snapshot_date)->toDateString(),
            'value' => (int) ($row->{$field} ?? 0),
        ])->values()->all();
    }

    /**
     * Hour-of-day × weekday heatmap based on successful + failed logins.
     *
     * @return list<array{weekday: int, hour: int, count: int}>
     */
    protected function activityHeatmap(?int $companyId, Carbon $from, Carbon $to): array
    {
        $driver = DB::connection()->getDriverName();
        $weekdayExpr = $driver === 'sqlite'
            ? "CAST(strftime('%w', logged_in_at) AS INTEGER)"
            : 'DAYOFWEEK(logged_in_at) - 1';
        $hourExpr = $driver === 'sqlite'
            ? "CAST(strftime('%H', logged_in_at) AS INTEGER)"
            : 'HOUR(logged_in_at)';

        $rows = UserLoginHistory::query()
            ->whereBetween('logged_in_at', [$from, $to])
            ->selectRaw("{$weekdayExpr} as weekday, {$hourExpr} as hour, COUNT(*) as aggregate")
            ->groupBy('weekday', 'hour')
            ->get();

        return $rows->map(fn ($row): array => [
            'weekday' => (int) $row->weekday,
            'hour' => (int) $row->hour,
            'count' => (int) $row->aggregate,
        ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function topFailedLoginIps(Carbon $from, Carbon $to): array
    {
        return UserLoginHistory::query()
            ->where('status', 'failed')
            ->whereBetween('logged_in_at', [$from, $to])
            ->selectRaw('ip_address, COUNT(*) as aggregate')
            ->groupBy('ip_address')
            ->orderByDesc('aggregate')
            ->limit(10)
            ->get()
            ->map(fn ($row): array => [
                'ip_address' => $row->ip_address ?: 'unknown',
                'count' => (int) $row->aggregate,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function apiKeyUsageSummary(): array
    {
        $summary = [
            'total' => 0,
            'active' => 0,
            'expired' => 0,
            'sanctum_tokens' => 0,
            'recent' => [],
        ];

        if (Schema::hasTable('cms_api_keys')) {
            $summary['total'] = CmsApiKey::query()->count();
            $summary['active'] = CmsApiKey::query()->where('is_active', true)->count();
            $summary['expired'] = CmsApiKey::query()
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now())
                ->count();
            $summary['recent'] = CmsApiKey::query()
                ->orderByDesc('last_used_at')
                ->limit(20)
                ->get(['uuid', 'name', 'last_used_at', 'is_active', 'expires_at'])
                ->map(fn (CmsApiKey $key): array => [
                    'uuid' => $key->uuid,
                    'name' => $key->name,
                    'last_used_at' => optional($key->last_used_at)?->toIso8601String(),
                    'is_active' => (bool) ($key->is_active ?? true),
                    'expires_at' => optional($key->expires_at)?->toIso8601String(),
                ])
                ->all();
        }

        if (Schema::hasTable('personal_access_tokens')) {
            $summary['sanctum_tokens'] = (int) DB::table('personal_access_tokens')->count();
        }

        return $summary;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function recentRoleEvents(int $limit): array
    {
        if (! Schema::hasTable('activity_log')) {
            return [];
        }

        return Activity::query()
            ->where('log_name', 'roles')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'event' => data_get($activity->properties, 'event', $activity->event),
                'description' => $activity->description,
                'causer_id' => $activity->causer_id,
                'created_at' => optional($activity->created_at)?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function recentAuditActions(?int $companyId, int $limit): array
    {
        if (! Schema::hasTable('audit_logs')) {
            return [];
        }

        return AuditLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->latest()
            ->limit($limit)
            ->get(['uuid', 'module', 'action', 'user_id', 'created_at'])
            ->map(fn (AuditLog $log): array => [
                'uuid' => $log->uuid,
                'module' => $log->module,
                'action' => $log->action,
                'user_id' => $log->user_id,
                'created_at' => optional($log->created_at)?->toIso8601String(),
            ])
            ->all();
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
