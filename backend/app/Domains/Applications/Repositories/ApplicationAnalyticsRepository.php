<?php

namespace App\Domains\Applications\Repositories;

use App\Domains\Applications\Models\ApplicationAnalyticsCountry;
use App\Domains\Applications\Models\ApplicationAnalyticsDaily;
use App\Domains\Applications\Models\ApplicationAnalyticsDevice;
use App\Domains\Applications\Models\ApplicationAnalyticsHeatmap;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationAnalyticsRepository extends BaseRepository
{
    public function __construct(ApplicationAnalyticsDaily $model)
    {
        parent::__construct($model);
    }

    /**
     * @return Collection<int, ApplicationAnalyticsDaily>
     */
    public function dailyInRange(int $applicationId, Carbon $from, Carbon $to): Collection
    {
        return ApplicationAnalyticsDaily::query()
            ->where('application_id', $applicationId)
            ->whereDate('metric_date', '>=', $from->toDateString())
            ->whereDate('metric_date', '<=', $to->toDateString())
            ->orderBy('metric_date')
            ->get();
    }

    public function latestDaily(int $applicationId): ?ApplicationAnalyticsDaily
    {
        return ApplicationAnalyticsDaily::query()
            ->where('application_id', $applicationId)
            ->orderByDesc('metric_date')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertDaily(int $applicationId, string $date, array $data): ApplicationAnalyticsDaily
    {
        $existing = ApplicationAnalyticsDaily::query()
            ->where('application_id', $applicationId)
            ->whereDate('metric_date', $date)
            ->first();

        if ($existing) {
            $existing->update($data);

            return $existing->refresh();
        }

        /** @var ApplicationAnalyticsDaily $row */
        $row = ApplicationAnalyticsDaily::query()->create(array_merge($data, [
            'application_id' => $applicationId,
            'metric_date' => $date,
        ]));

        return $row->refresh();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function upsertCountries(int $applicationId, string $date, array $rows): void
    {
        foreach ($rows as $row) {
            $code = strtoupper((string) $row['country_code']);
            $existing = ApplicationAnalyticsCountry::query()
                ->where('application_id', $applicationId)
                ->whereDate('metric_date', $date)
                ->where('country_code', $code)
                ->first();

            $payload = [
                'country_name' => $row['country_name'] ?? null,
                'users' => (int) ($row['users'] ?? 0),
                'sessions' => (int) ($row['sessions'] ?? 0),
                'installs' => (int) ($row['installs'] ?? 0),
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                ApplicationAnalyticsCountry::query()->create(array_merge($payload, [
                    'application_id' => $applicationId,
                    'metric_date' => $date,
                    'country_code' => $code,
                ]));
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function upsertDevices(int $applicationId, string $date, array $rows): void
    {
        ApplicationAnalyticsDevice::query()
            ->where('application_id', $applicationId)
            ->whereDate('metric_date', $date)
            ->delete();

        foreach ($rows as $row) {
            ApplicationAnalyticsDevice::query()->create([
                'application_id' => $applicationId,
                'metric_date' => $date,
                'device_model' => $row['device_model'] ?? null,
                'os_name' => $row['os_name'] ?? null,
                'os_version' => $row['os_version'] ?? null,
                'users' => (int) ($row['users'] ?? 0),
                'sessions' => (int) ($row['sessions'] ?? 0),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function upsertHeatmap(int $applicationId, string $date, array $rows): void
    {
        foreach ($rows as $row) {
            $day = (int) $row['day_of_week'];
            $hour = (int) $row['hour'];
            $existing = ApplicationAnalyticsHeatmap::query()
                ->where('application_id', $applicationId)
                ->whereDate('metric_date', $date)
                ->where('day_of_week', $day)
                ->where('hour', $hour)
                ->first();

            $payload = [
                'activity_count' => (int) ($row['activity_count'] ?? 0),
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                ApplicationAnalyticsHeatmap::query()->create(array_merge($payload, [
                    'application_id' => $applicationId,
                    'metric_date' => $date,
                    'day_of_week' => $day,
                    'hour' => $hour,
                ]));
            }
        }
    }

    /**
     * @return Collection<int, object>
     */
    public function countryTotals(int $applicationId, Carbon $from, Carbon $to, int $limit = 25): Collection
    {
        return ApplicationAnalyticsCountry::query()
            ->where('application_id', $applicationId)
            ->whereDate('metric_date', '>=', $from->toDateString())
            ->whereDate('metric_date', '<=', $to->toDateString())
            ->select(
                'country_code',
                DB::raw('MAX(country_name) as country_name'),
                DB::raw('SUM(users) as users'),
                DB::raw('SUM(sessions) as sessions'),
                DB::raw('SUM(installs) as installs')
            )
            ->groupBy('country_code')
            ->orderByDesc('users')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function deviceTotals(int $applicationId, Carbon $from, Carbon $to, int $limit = 25): Collection
    {
        return ApplicationAnalyticsDevice::query()
            ->where('application_id', $applicationId)
            ->whereDate('metric_date', '>=', $from->toDateString())
            ->whereDate('metric_date', '<=', $to->toDateString())
            ->select(
                'device_model',
                'os_name',
                'os_version',
                DB::raw('SUM(users) as users'),
                DB::raw('SUM(sessions) as sessions')
            )
            ->groupBy('device_model', 'os_name', 'os_version')
            ->orderByDesc('users')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function osTotals(int $applicationId, Carbon $from, Carbon $to, int $limit = 25): Collection
    {
        return ApplicationAnalyticsDevice::query()
            ->where('application_id', $applicationId)
            ->whereDate('metric_date', '>=', $from->toDateString())
            ->whereDate('metric_date', '<=', $to->toDateString())
            ->select(
                'os_name',
                'os_version',
                DB::raw('SUM(users) as users'),
                DB::raw('SUM(sessions) as sessions')
            )
            ->groupBy('os_name', 'os_version')
            ->orderByDesc('users')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function heatmapMatrix(int $applicationId, Carbon $from, Carbon $to): Collection
    {
        return ApplicationAnalyticsHeatmap::query()
            ->where('application_id', $applicationId)
            ->whereDate('metric_date', '>=', $from->toDateString())
            ->whereDate('metric_date', '<=', $to->toDateString())
            ->select(
                'day_of_week',
                'hour',
                DB::raw('SUM(activity_count) as activity_count')
            )
            ->groupBy('day_of_week', 'hour')
            ->orderBy('day_of_week')
            ->orderBy('hour')
            ->get();
    }

    /**
     * @return array<string, int|float>
     */
    public function summaryTotals(int $applicationId, Carbon $from, Carbon $to): array
    {
        $rows = $this->dailyInRange($applicationId, $from, $to);
        $latest = $rows->last();

        return [
            'active_users' => (int) ($latest?->active_users ?? 0),
            'daily_users' => (int) ($latest?->daily_users ?? 0),
            'monthly_users' => (int) ($latest?->monthly_users ?? 0),
            'avg_session_seconds' => (int) round((float) ($rows->avg('avg_session_seconds') ?? 0)),
            'installs' => (int) $rows->sum('installs'),
            'uninstalls' => (int) $rows->sum('uninstalls'),
            'sessions' => (int) $rows->sum('sessions'),
            'retention_d1' => (float) ($latest?->retention_d1 ?? 0),
            'retention_d7' => (float) ($latest?->retention_d7 ?? 0),
            'retention_d30' => (float) ($latest?->retention_d30 ?? 0),
            'days' => $rows->count(),
        ];
    }
}
