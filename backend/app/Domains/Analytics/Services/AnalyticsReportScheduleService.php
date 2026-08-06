<?php

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Enums\AnalyticsReportFormat;
use App\Domains\Analytics\Models\AnalyticsReport;
use App\Domains\Scheduler\Enums\ScheduledJobHandler;
use App\Domains\Scheduler\Enums\ScheduledJobType;
use App\Domains\Scheduler\Services\ScheduledJobService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsReportScheduleService
{
    public function __construct(
        private readonly ScheduledJobService $scheduledJobService,
    ) {}

    /**
     * Sync report schedule_config with a ScheduledJob row.
     *
     * @param  array<string, mixed>  $config
     */
    public function sync(AnalyticsReport $report, array $config, User $actor): AnalyticsReport
    {
        return DB::transaction(function () use ($report, $config, $actor): AnalyticsReport {
            $enabled = (bool) ($config['enabled'] ?? false);
            $cron = (string) ($config['cron'] ?? ScheduledJobHandler::AnalyticsReport->defaultCron());
            $format = (string) ($config['format'] ?? $report->format_defaults['format'] ?? AnalyticsReportFormat::Csv->value);
            $timezone = (string) ($config['timezone'] ?? 'UTC');

            if (! $enabled) {
                return $this->disable($report, $actor, keepConfig: true);
            }

            $payload = [
                'report_uuid' => $report->uuid,
                'format' => $format,
            ];

            if ($report->scheduled_job_id && $report->scheduledJob) {
                $job = $this->scheduledJobService->update($report->scheduledJob->uuid, [
                    'name' => 'Analytics Report: '.$report->name,
                    'description' => 'Scheduled generation for report '.$report->uuid,
                    'handler_key' => ScheduledJobHandler::AnalyticsReport->value,
                    'job_type' => ScheduledJobType::Cron->value,
                    'schedule_cron' => $cron,
                    'timezone' => $timezone,
                    'payload' => $payload,
                    'is_enabled' => true,
                ], $actor);
            } else {
                $job = $this->scheduledJobService->create([
                    'name' => 'Analytics Report: '.$report->name,
                    'description' => 'Scheduled generation for report '.$report->uuid,
                    'handler_key' => ScheduledJobHandler::AnalyticsReport->value,
                    'job_type' => ScheduledJobType::Cron->value,
                    'schedule_cron' => $cron,
                    'timezone' => $timezone,
                    'payload' => $payload,
                    'is_enabled' => true,
                    'queue_name' => 'default',
                ], $actor);
            }

            $report->update([
                'scheduled_job_id' => $job->id,
                'is_scheduled' => true,
                'schedule_config' => [
                    'enabled' => true,
                    'cron' => $cron,
                    'format' => $format,
                    'timezone' => $timezone,
                    'scheduled_job_uuid' => $job->uuid,
                ],
                'updated_by' => $actor->id,
            ]);

            return $report->fresh(['scheduledJob']);
        });
    }

    public function disable(AnalyticsReport $report, User $actor, bool $keepConfig = false): AnalyticsReport
    {
        return DB::transaction(function () use ($report, $actor, $keepConfig): AnalyticsReport {
            if ($report->scheduledJob) {
                $this->scheduledJobService->toggle($report->scheduledJob->uuid, $actor, false);
            }

            $config = is_array($report->schedule_config) ? $report->schedule_config : [];
            if ($keepConfig) {
                $config['enabled'] = false;
            } else {
                $config = null;
            }

            $report->update([
                'is_scheduled' => false,
                'schedule_config' => $config,
                'updated_by' => $actor->id,
            ]);

            return $report->fresh(['scheduledJob']);
        });
    }
}
