<?php

namespace App\Domains\Applications\Repositories;

use App\Domains\Applications\Models\ApplicationCrashReport;
use App\Domains\Applications\Models\ApplicationHealthMetric;
use App\Domains\Applications\Models\ApplicationMonitoringAlert;
use App\Domains\Applications\Models\ApplicationMonitoringAlertEvent;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationMonitoringRepository extends BaseRepository
{
    public function __construct(ApplicationCrashReport $model)
    {
        parent::__construct($model);
    }

    public function findCrashForApplication(int $applicationId, string $identifier, bool $withTrashed = false): ApplicationCrashReport
    {
        $query = ApplicationCrashReport::query()->where('application_id', $applicationId);

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var ApplicationCrashReport|null $crash */
        $crash = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        if (! $crash) {
            abort(404, 'Crash report not found.');
        }

        return $crash;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateCrashes(int $applicationId, array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));

        return $this->crashFilteredQuery($applicationId, $filters)
            ->with(['version:id,uuid,version_number,status', 'creator:id,uuid,full_name,email'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function crashFilteredQuery(int $applicationId, array $filters = []): Builder
    {
        $query = ApplicationCrashReport::query()->where('application_id', $applicationId);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        if (! empty($filters['version_label'])) {
            $query->where('version_label', $filters['version_label']);
        }
        if (! empty($filters['device_os'])) {
            $query->where('device_os', $filters['device_os']);
        }
        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('device_model', 'like', "%{$search}%")
                    ->orWhere('fingerprint', 'like', "%{$search}%");
            });
        }

        $sortBy = in_array($filters['sort_by'] ?? '', [
            'occurred_at', 'created_at', 'severity', 'status', 'occurrence_count', 'title',
        ], true) ? $filters['sort_by'] : 'occurred_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @return array<string, int>
     */
    public function crashSummary(int $applicationId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = ApplicationCrashReport::query()->where('application_id', $applicationId);
        if ($from) {
            $query->where('occurred_at', '>=', $from);
        }
        if ($to) {
            $query->where('occurred_at', '<=', $to);
        }

        $byType = (clone $query)
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->all();

        $open = (clone $query)->where('status', 'open')->count();

        return [
            'total' => array_sum($byType),
            'crash' => (int) ($byType['crash'] ?? 0),
            'anr' => (int) ($byType['anr'] ?? 0),
            'api_error' => (int) ($byType['api_error'] ?? 0),
            'open' => $open,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function crashesByDay(int $applicationId, Carbon $from, Carbon $to): Collection
    {
        return ApplicationCrashReport::query()
            ->where('application_id', $applicationId)
            ->whereBetween('occurred_at', [$from, $to])
            ->selectRaw('DATE(occurred_at) as day, type, COUNT(*) as total')
            ->groupBy('day', 'type')
            ->orderBy('day')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function deviceStatistics(int $applicationId, int $limit = 20): Collection
    {
        return ApplicationCrashReport::query()
            ->where('application_id', $applicationId)
            ->selectRaw('device_model, device_os, device_os_version, COUNT(*) as total, COUNT(DISTINCT device_id) as devices')
            ->whereNotNull('device_model')
            ->groupBy('device_model', 'device_os', 'device_os_version')
            ->orderByDesc('total')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    public function findOpenByFingerprint(int $applicationId, string $fingerprint): ?ApplicationCrashReport
    {
        return ApplicationCrashReport::query()
            ->where('application_id', $applicationId)
            ->where('fingerprint', $fingerprint)
            ->whereIn('status', ['open', 'investigating'])
            ->orderByDesc('occurred_at')
            ->first();
    }

    /** @param  array<string, mixed>  $data */
    public function createCrash(array $data): ApplicationCrashReport
    {
        /** @var ApplicationCrashReport $crash */
        $crash = ApplicationCrashReport::query()->create($data);

        return $crash->fresh(['version', 'creator']) ?? $crash;
    }

    /** @param  array<string, mixed>  $data */
    public function updateCrash(ApplicationCrashReport $crash, array $data): ApplicationCrashReport
    {
        $crash->update($data);

        return $crash->refresh()->load(['version', 'creator', 'updater']);
    }

    /** @param  array<string, mixed>  $data */
    public function createHealthMetric(array $data): ApplicationHealthMetric
    {
        /** @var ApplicationHealthMetric $metric */
        $metric = ApplicationHealthMetric::query()->create($data);

        return $metric;
    }

    /**
     * @return Collection<int, ApplicationHealthMetric>
     */
    public function recentHealthMetrics(int $applicationId, int $limit = 30): Collection
    {
        return ApplicationHealthMetric::query()
            ->where('application_id', $applicationId)
            ->orderByDesc('recorded_at')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    public function latestHealthMetric(int $applicationId): ?ApplicationHealthMetric
    {
        return ApplicationHealthMetric::query()
            ->where('application_id', $applicationId)
            ->orderByDesc('recorded_at')
            ->first();
    }

    /**
     * @return Collection<int, object>
     */
    public function healthChartSeries(int $applicationId, Carbon $from, Carbon $to): Collection
    {
        return ApplicationHealthMetric::query()
            ->where('application_id', $applicationId)
            ->whereBetween('recorded_at', [$from, $to])
            ->orderBy('recorded_at')
            ->get([
                'recorded_at',
                'health_score',
                'crash_rate',
                'anr_rate',
                'api_error_rate',
                'avg_response_time_ms',
                'avg_memory_usage_mb',
                'avg_battery_usage',
            ]);
    }

    /** @param  array<string, mixed>  $data */
    public function createAlert(array $data): ApplicationMonitoringAlert
    {
        /** @var ApplicationMonitoringAlert $alert */
        $alert = ApplicationMonitoringAlert::query()->create($data);

        return $alert;
    }

    /** @param  array<string, mixed>  $data */
    public function updateAlert(ApplicationMonitoringAlert $alert, array $data): ApplicationMonitoringAlert
    {
        $alert->update($data);

        return $alert->refresh();
    }

    public function findAlertForApplication(int $applicationId, string $identifier): ApplicationMonitoringAlert
    {
        $query = ApplicationMonitoringAlert::query()->where('application_id', $applicationId);

        /** @var ApplicationMonitoringAlert|null $alert */
        $alert = $query->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        if (! $alert) {
            abort(404, 'Monitoring alert not found.');
        }

        return $alert;
    }

    /**
     * @return Collection<int, ApplicationMonitoringAlert>
     */
    public function alertsForApplication(int $applicationId): Collection
    {
        return ApplicationMonitoringAlert::query()
            ->where('application_id', $applicationId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, ApplicationMonitoringAlert>
     */
    public function activeAlerts(int $applicationId): Collection
    {
        return ApplicationMonitoringAlert::query()
            ->where('application_id', $applicationId)
            ->where('is_active', true)
            ->get();
    }

    /** @param  array<string, mixed>  $data */
    public function createAlertEvent(array $data): ApplicationMonitoringAlertEvent
    {
        /** @var ApplicationMonitoringAlertEvent $event */
        $event = ApplicationMonitoringAlertEvent::query()->create($data);

        return $event;
    }

    /**
     * @return Collection<int, ApplicationMonitoringAlertEvent>
     */
    public function recentAlertEvents(int $applicationId, int $limit = 25): Collection
    {
        return ApplicationMonitoringAlertEvent::query()
            ->where('application_id', $applicationId)
            ->with(['alert:id,uuid,name', 'acknowledger:id,uuid,full_name,email'])
            ->orderByDesc('triggered_at')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    public function findAlertEventForApplication(int $applicationId, string $identifier): ApplicationMonitoringAlertEvent
    {
        /** @var ApplicationMonitoringAlertEvent|null $event */
        $event = ApplicationMonitoringAlertEvent::query()
            ->where('application_id', $applicationId)
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        if (! $event) {
            abort(404, 'Alert event not found.');
        }

        return $event;
    }

    /**
     * @return array<string, float|int>
     */
    public function aggregateRates(int $applicationId, Carbon $from, Carbon $to): array
    {
        $rows = ApplicationCrashReport::query()
            ->where('application_id', $applicationId)
            ->whereBetween('occurred_at', [$from, $to])
            ->select('type', DB::raw('COUNT(*) as total'), DB::raw('AVG(response_time_ms) as avg_rt'), DB::raw('AVG(memory_usage_mb) as avg_mem'), DB::raw('AVG(battery_level) as avg_batt'))
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $total = max(1, (int) $rows->sum('total'));

        return [
            'crash_count' => (int) ($rows->get('crash')?->total ?? 0),
            'anr_count' => (int) ($rows->get('anr')?->total ?? 0),
            'api_error_count' => (int) ($rows->get('api_error')?->total ?? 0),
            'crash_rate' => round(((int) ($rows->get('crash')?->total ?? 0)) / $total * 100, 4),
            'anr_rate' => round(((int) ($rows->get('anr')?->total ?? 0)) / $total * 100, 4),
            'api_error_rate' => round(((int) ($rows->get('api_error')?->total ?? 0)) / $total * 100, 4),
            'avg_response_time_ms' => (int) round((float) ($rows->get('api_error')?->avg_rt ?? $rows->avg('avg_rt') ?? 0)),
            'avg_memory_usage_mb' => round((float) ($rows->avg('avg_mem') ?? 0), 2),
            'avg_battery_usage' => round(100 - (float) ($rows->avg('avg_batt') ?? 100), 2),
            'sample_size' => (int) $rows->sum('total'),
        ];
    }
}
