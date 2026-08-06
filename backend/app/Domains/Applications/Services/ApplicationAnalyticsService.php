<?php

namespace App\Domains\Applications\Services;

use App\Domains\Applications\Events\ApplicationAnalyticsIngested;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationAnalyticsDaily;
use App\Domains\Applications\Repositories\ApplicationAnalyticsRepository;
use App\Domains\Applications\Repositories\ApplicationRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApplicationAnalyticsService
{
    public function __construct(
        private readonly ApplicationAnalyticsRepository $analyticsRepository,
        private readonly ApplicationRepository $applicationRepository,
    ) {}

    public function resolveApplication(string $identifier): Application
    {
        return $this->applicationRepository->findByIdentifierOrFail($identifier);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(string $applicationIdentifier, ?string $from = null, ?string $to = null): array
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $range = $this->resolveRange($from, $to, 30);
        $daily = $this->analyticsRepository->dailyInRange($application->id, $range['from'], $range['to']);
        $summary = $this->analyticsRepository->summaryTotals($application->id, $range['from'], $range['to']);

        return [
            'application' => $application,
            'summary' => $summary,
            'latest' => $this->analyticsRepository->latestDaily($application->id),
            'trend' => [
                'labels' => $daily->map(fn (ApplicationAnalyticsDaily $row) => optional($row->metric_date)->toDateString())->values()->all(),
                'daily_users' => $daily->pluck('daily_users')->values()->all(),
                'monthly_users' => $daily->pluck('monthly_users')->values()->all(),
                'active_users' => $daily->pluck('active_users')->values()->all(),
                'installs' => $daily->pluck('installs')->values()->all(),
                'uninstalls' => $daily->pluck('uninstalls')->values()->all(),
                'avg_session_seconds' => $daily->pluck('avg_session_seconds')->values()->all(),
                'retention_d1' => $daily->pluck('retention_d1')->values()->all(),
                'retention_d7' => $daily->pluck('retention_d7')->values()->all(),
                'retention_d30' => $daily->pluck('retention_d30')->values()->all(),
            ],
            'top_countries' => $this->analyticsRepository->countryTotals($application->id, $range['from'], $range['to'], 5),
            'top_devices' => $this->analyticsRepository->deviceTotals($application->id, $range['from'], $range['to'], 5),
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function trends(string $applicationIdentifier, ?string $metric = null, ?string $from = null, ?string $to = null): array
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $range = $this->resolveRange($from, $to, 30);
        $daily = $this->analyticsRepository->dailyInRange($application->id, $range['from'], $range['to']);
        $metricKey = $metric ?: 'daily_users';

        $values = match ($metricKey) {
            'active_users' => $daily->pluck('active_users')->values()->all(),
            'monthly_users' => $daily->pluck('monthly_users')->values()->all(),
            'avg_session_seconds' => $daily->pluck('avg_session_seconds')->values()->all(),
            'installs' => $daily->pluck('installs')->values()->all(),
            'uninstalls' => $daily->pluck('uninstalls')->values()->all(),
            'retention_d1' => $daily->pluck('retention_d1')->values()->all(),
            'retention_d7' => $daily->pluck('retention_d7')->values()->all(),
            'retention_d30' => $daily->pluck('retention_d30')->values()->all(),
            'sessions' => $daily->pluck('sessions')->values()->all(),
            default => $daily->pluck('daily_users')->values()->all(),
        };

        $previous = $this->previousPeriodRange($range['from'], $range['to']);
        $previousDaily = $this->analyticsRepository->dailyInRange($application->id, $previous['from'], $previous['to']);
        $currentTotal = array_sum(array_map('floatval', $values));
        $previousTotal = (float) $previousDaily->sum($metricKey === 'daily_users' ? 'daily_users' : (
            in_array($metricKey, ['active_users', 'monthly_users', 'installs', 'uninstalls', 'sessions', 'avg_session_seconds'], true)
                ? $metricKey
                : (str_starts_with($metricKey, 'retention_') ? $metricKey : 'daily_users')
        ));

        $changePct = $previousTotal > 0
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 2)
            : ($currentTotal > 0 ? 100.0 : 0.0);

        return [
            'application' => $application,
            'metric' => $metricKey,
            'labels' => $daily->map(fn (ApplicationAnalyticsDaily $row) => optional($row->metric_date)->toDateString())->values()->all(),
            'values' => $values,
            'change_percent' => $changePct,
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function heatmap(string $applicationIdentifier, ?string $from = null, ?string $to = null): array
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $range = $this->resolveRange($from, $to, 14);
        $rows = $this->analyticsRepository->heatmapMatrix($application->id, $range['from'], $range['to']);

        $matrix = [];
        for ($day = 0; $day < 7; $day++) {
            $matrix[$day] = array_fill(0, 24, 0);
        }

        $max = 0;
        foreach ($rows as $row) {
            $day = (int) $row->day_of_week;
            $hour = (int) $row->hour;
            if ($day < 0 || $day > 6 || $hour < 0 || $hour > 23) {
                continue;
            }
            $matrix[$day][$hour] = (int) $row->activity_count;
            $max = max($max, (int) $row->activity_count);
        }

        return [
            'application' => $application,
            'days' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'hours' => range(0, 23),
            'matrix' => $matrix,
            'max' => $max,
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function countries(string $applicationIdentifier, ?string $from = null, ?string $to = null, int $limit = 25): array
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $range = $this->resolveRange($from, $to, 30);

        return [
            'application' => $application,
            'countries' => $this->analyticsRepository->countryTotals($application->id, $range['from'], $range['to'], $limit),
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function devices(string $applicationIdentifier, ?string $from = null, ?string $to = null, int $limit = 25): array
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $range = $this->resolveRange($from, $to, 30);

        return [
            'application' => $application,
            'devices' => $this->analyticsRepository->deviceTotals($application->id, $range['from'], $range['to'], $limit),
            'os_versions' => $this->analyticsRepository->osTotals($application->id, $range['from'], $range['to'], $limit),
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ingest(string $applicationIdentifier, array $data, ?User $actor = null): ApplicationAnalyticsDaily
    {
        return DB::transaction(function () use ($applicationIdentifier, $data, $actor): ApplicationAnalyticsDaily {
            $application = $this->resolveApplication($applicationIdentifier);
            $date = Carbon::parse((string) ($data['metric_date'] ?? now()->toDateString()))->toDateString();

            $daily = $this->analyticsRepository->upsertDaily($application->id, $date, [
                'active_users' => (int) ($data['active_users'] ?? 0),
                'daily_users' => (int) ($data['daily_users'] ?? $data['active_users'] ?? 0),
                'monthly_users' => (int) ($data['monthly_users'] ?? 0),
                'avg_session_seconds' => (int) ($data['avg_session_seconds'] ?? 0),
                'retention_d1' => (float) ($data['retention_d1'] ?? 0),
                'retention_d7' => (float) ($data['retention_d7'] ?? 0),
                'retention_d30' => (float) ($data['retention_d30'] ?? 0),
                'installs' => (int) ($data['installs'] ?? 0),
                'uninstalls' => (int) ($data['uninstalls'] ?? 0),
                'sessions' => (int) ($data['sessions'] ?? 0),
                'metadata' => $data['metadata'] ?? null,
                'updated_by' => $actor?->id,
                'created_by' => $actor?->id,
            ]);

            if (! empty($data['countries']) && is_array($data['countries'])) {
                $this->analyticsRepository->upsertCountries($application->id, $date, $data['countries']);
            }

            if (! empty($data['devices']) && is_array($data['devices'])) {
                $this->analyticsRepository->upsertDevices($application->id, $date, $data['devices']);
            }

            if (! empty($data['heatmap']) && is_array($data['heatmap'])) {
                $this->analyticsRepository->upsertHeatmap($application->id, $date, $data['heatmap']);
            }

            event(new ApplicationAnalyticsIngested($daily, $actor));

            return $daily;
        });
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    protected function resolveRange(?string $from, ?string $to, int $defaultDays): array
    {
        $end = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $start = $from ? Carbon::parse($from)->startOfDay() : (clone $end)->subDays($defaultDays - 1)->startOfDay();

        if ($end->lt($start)) {
            throw new ApiException('End date must be after start date.', 422);
        }

        return ['from' => $start, 'to' => $end];
    }

    /**
     * @param  array{from: Carbon, to: Carbon}  $range ignored — uses from/to
     * @return array{from: Carbon, to: Carbon}
     */
    protected function previousPeriodRange(Carbon $from, Carbon $to): array
    {
        $days = $from->diffInDays($to) + 1;

        return [
            'from' => $from->copy()->subDays($days)->startOfDay(),
            'to' => $from->copy()->subDay()->endOfDay(),
        ];
    }
}
