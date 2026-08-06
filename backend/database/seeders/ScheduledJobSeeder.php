<?php

namespace Database\Seeders;

use App\Domains\Scheduler\Enums\ScheduledJobHandler;
use App\Domains\Scheduler\Enums\ScheduledJobType;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Domains\Scheduler\Services\SchedulerEngineService;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScheduledJobSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->orderBy('id')->first();
        $engine = app(SchedulerEngineService::class);

        $definitions = [
            [
                'name' => 'Daily Operational Report',
                'description' => 'Generate daily operational reports every morning.',
                'job_type' => ScheduledJobType::Cron->value,
                'handler_key' => ScheduledJobHandler::DailyReport->value,
                'schedule_cron' => ScheduledJobHandler::DailyReport->defaultCron(),
            ],
            [
                'name' => 'Weekly Platform Backup',
                'description' => 'Weekly backup checklist every Sunday.',
                'job_type' => ScheduledJobType::Recurring->value,
                'handler_key' => ScheduledJobHandler::WeeklyBackup->value,
                'schedule_cron' => ScheduledJobHandler::WeeklyBackup->defaultCron(),
            ],
            [
                'name' => 'Monthly Invoice Cycle',
                'description' => 'Prepare monthly invoice generation on the 1st.',
                'job_type' => ScheduledJobType::Cron->value,
                'handler_key' => ScheduledJobHandler::MonthlyInvoice->value,
                'schedule_cron' => ScheduledJobHandler::MonthlyInvoice->defaultCron(),
            ],
            [
                'name' => 'Platform Health Check',
                'description' => 'Capture monitoring health snapshot every 5 minutes.',
                'job_type' => ScheduledJobType::Cron->value,
                'handler_key' => ScheduledJobHandler::HealthCheck->value,
                'schedule_cron' => ScheduledJobHandler::HealthCheck->defaultCron(),
            ],
            [
                'name' => 'Customer Reminder Sweep',
                'description' => 'Send pending customer reminders daily at 09:00.',
                'job_type' => ScheduledJobType::Recurring->value,
                'handler_key' => ScheduledJobHandler::CustomerReminder->value,
                'schedule_cron' => ScheduledJobHandler::CustomerReminder->defaultCron(),
                'payload' => ['limit' => 100],
            ],
            [
                'name' => 'Subscription Renewal Sweep',
                'description' => 'Process upcoming subscription renewals daily.',
                'job_type' => ScheduledJobType::Cron->value,
                'handler_key' => ScheduledJobHandler::SubscriptionRenewal->value,
                'schedule_cron' => ScheduledJobHandler::SubscriptionRenewal->defaultCron(),
                'payload' => ['window_days' => 7],
            ],
            [
                'name' => 'Delete Expired Data',
                'description' => 'Purge expired retention data overnight.',
                'job_type' => ScheduledJobType::Cron->value,
                'handler_key' => ScheduledJobHandler::DeleteExpiredData->value,
                'schedule_cron' => ScheduledJobHandler::DeleteExpiredData->defaultCron(),
                'payload' => ['retention_days' => 90],
            ],
        ];

        foreach ($definitions as $definition) {
            $job = ScheduledJob::query()->firstOrCreate(
                ['name' => $definition['name']],
                array_merge($definition, [
                    'timezone' => 'UTC',
                    'queue_name' => 'default',
                    'is_enabled' => true,
                    'without_overlapping' => true,
                    'max_attempts' => 3,
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                ]),
            );

            if ($job->wasRecentlyCreated || blank($job->next_run_at)) {
                $job->next_run_at = $engine->computeNextRun($job);
                $job->save();
            }
        }
    }
}
