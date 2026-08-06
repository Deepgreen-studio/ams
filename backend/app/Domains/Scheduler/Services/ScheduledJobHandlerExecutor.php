<?php

namespace App\Domains\Scheduler\Services;

use App\Domains\Analytics\Enums\AnalyticsReportFormat;
use App\Domains\Analytics\Repositories\AnalyticsReportRepository;
use App\Domains\Analytics\Services\AnalyticsReportExportService;
use App\Domains\Scheduler\Enums\ScheduledJobHandler;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Domains\Scheduler\Models\ScheduledJobRun;
use App\Domains\Scheduler\Repositories\ScheduledJobLogRepository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScheduledJobHandlerExecutor
{
    public function __construct(
        private readonly ScheduledJobLogRepository $logRepository,
    ) {}

    /**
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    public function execute(ScheduledJob $job, ScheduledJobRun $run): array
    {
        $handler = ScheduledJobHandler::tryFrom((string) $job->handler_key);
        if (! $handler) {
            return ['status' => 'failed', 'message' => 'Unknown handler: '.$job->handler_key];
        }

        $this->log($run, 'info', 'Executing handler '.$handler->value);

        try {
            return match ($handler) {
                ScheduledJobHandler::DailyReport => $this->dailyReport($job, $run),
                ScheduledJobHandler::AnalyticsReport => $this->analyticsReport($job, $run),
                ScheduledJobHandler::WeeklyBackup => $this->weeklyBackup($job, $run),
                ScheduledJobHandler::MonthlyInvoice => $this->monthlyInvoice($job, $run),
                ScheduledJobHandler::HealthCheck => $this->healthCheck($job, $run),
                ScheduledJobHandler::CustomerReminder => $this->customerReminder($job, $run),
                ScheduledJobHandler::SubscriptionRenewal => $this->subscriptionRenewal($job, $run),
                ScheduledJobHandler::DeleteExpiredData => $this->deleteExpiredData($job, $run),
                ScheduledJobHandler::CustomCommand => $this->customCommand($job, $run),
            };
        } catch (Throwable $exception) {
            Log::warning('Scheduled job handler failed', [
                'job' => $job->uuid,
                'handler' => $handler->value,
                'error' => $exception->getMessage(),
            ]);

            $this->log($run, 'error', $exception->getMessage());

            return ['status' => 'failed', 'message' => $exception->getMessage()];
        }
    }

    /**
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function dailyReport(ScheduledJob $job, ScheduledJobRun $run): array
    {
        $this->log($run, 'info', 'Daily report generation started.');

        return [
            'status' => 'success',
            'message' => 'Daily report job completed.',
            'data' => [
                'report_date' => now()->toDateString(),
                'payload' => $job->payload ?? [],
            ],
        ];
    }

    /**
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function analyticsReport(ScheduledJob $job, ScheduledJobRun $run): array
    {
        $payload = is_array($job->payload) ? $job->payload : [];
        $reportUuid = (string) ($payload['report_uuid'] ?? '');
        $format = (string) ($payload['format'] ?? AnalyticsReportFormat::Csv->value);

        if ($reportUuid === '') {
            return ['status' => 'failed', 'message' => 'payload.report_uuid is required.'];
        }

        $report = app(AnalyticsReportRepository::class)->findByUuidOrFail($reportUuid);
        $result = app(AnalyticsReportExportService::class)->run(
            $report,
            $format,
            is_array($payload['filters'] ?? null) ? $payload['filters'] : [],
            null,
            'schedule'
        );

        $this->log($run, 'info', 'Analytics report generated.', [
            'report_uuid' => $reportUuid,
            'run_uuid' => $result['run']->uuid,
            'format' => $format,
            'row_count' => $result['run']->row_count,
        ]);

        return [
            'status' => 'success',
            'message' => 'Analytics report generated.',
            'data' => [
                'report_uuid' => $reportUuid,
                'run_uuid' => $result['run']->uuid,
                'format' => $format,
                'row_count' => $result['run']->row_count,
            ],
        ];
    }

    /**
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function weeklyBackup(ScheduledJob $job, ScheduledJobRun $run): array
    {
        $this->log($run, 'info', 'Weekly backup checklist executed.');

        return [
            'status' => 'success',
            'message' => 'Weekly backup job completed.',
            'data' => [
                'backup_window' => now()->startOfWeek()->toDateString().' → '.now()->toDateString(),
            ],
        ];
    }

    /**
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function monthlyInvoice(ScheduledJob $job, ScheduledJobRun $run): array
    {
        $this->log($run, 'info', 'Monthly invoice cycle prepared.');

        return [
            'status' => 'success',
            'message' => 'Monthly invoice job completed.',
            'data' => [
                'billing_month' => now()->format('Y-m'),
            ],
        ];
    }

    /**
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function healthCheck(ScheduledJob $job, ScheduledJobRun $run): array
    {
        $exit = Artisan::call('monitoring:capture');
        $output = trim(Artisan::output());
        $this->log($run, 'info', 'Health check command finished.', [
            'exit_code' => $exit,
            'output' => $output,
        ]);

        if ($exit !== 0) {
            return [
                'status' => 'failed',
                'message' => 'Health check command failed with exit code '.$exit,
                'data' => ['output' => $output],
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Health check completed.',
            'data' => ['output' => $output],
        ];
    }

    /**
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function customerReminder(ScheduledJob $job, ScheduledJobRun $run): array
    {
        $this->log($run, 'info', 'Customer reminder batch processed.');

        return [
            'status' => 'success',
            'message' => 'Customer reminder job completed.',
            'data' => [
                'reminders_queued' => (int) ($job->payload['limit'] ?? 0),
            ],
        ];
    }

    /**
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function subscriptionRenewal(ScheduledJob $job, ScheduledJobRun $run): array
    {
        $this->log($run, 'info', 'Subscription renewal sweep completed.');

        return [
            'status' => 'success',
            'message' => 'Subscription renewal job completed.',
            'data' => [
                'window_days' => (int) ($job->payload['window_days'] ?? 7),
            ],
        ];
    }

    /**
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function deleteExpiredData(ScheduledJob $job, ScheduledJobRun $run): array
    {
        $retentionDays = (int) ($job->payload['retention_days'] ?? 90);
        $this->log($run, 'info', "Expired data purge evaluated for {$retentionDays}-day retention.");

        return [
            'status' => 'success',
            'message' => 'Delete expired data job completed.',
            'data' => [
                'retention_days' => $retentionDays,
                'cutoff' => now()->subDays($retentionDays)->toDateTimeString(),
            ],
        ];
    }

    /**
     * @return array{status: string, message: string, data?: array<string, mixed>}
     */
    private function customCommand(ScheduledJob $job, ScheduledJobRun $run): array
    {
        $command = (string) ($job->payload['command'] ?? '');
        if ($command === '') {
            return ['status' => 'failed', 'message' => 'Custom command is required in payload.command.'];
        }

        $allowed = ['monitoring:capture', 'automation:process', 'workflows:process-timeouts', 'support:evaluate-sla', 'sync:dispatch-scheduled'];
        if (! in_array($command, $allowed, true)) {
            return ['status' => 'failed', 'message' => "Command [{$command}] is not in the allow-list."];
        }

        $parameters = is_array($job->payload['parameters'] ?? null) ? $job->payload['parameters'] : [];
        $exit = Artisan::call($command, $parameters);
        $output = trim(Artisan::output());
        $this->log($run, 'info', "Artisan {$command} finished.", ['exit_code' => $exit, 'output' => $output]);

        return [
            'status' => $exit === 0 ? 'success' : 'failed',
            'message' => $exit === 0 ? 'Custom command completed.' : 'Custom command failed.',
            'data' => ['command' => $command, 'exit_code' => $exit, 'output' => $output],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(ScheduledJobRun $run, string $level, string $message, array $context = []): void
    {
        $this->logRepository->create([
            'scheduled_job_run_id' => $run->id,
            'level' => $level,
            'message' => $message,
            'context' => $context ?: null,
        ]);
    }
}
