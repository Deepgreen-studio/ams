<?php

namespace App\Domains\Scheduler\Services;

use App\Domains\Scheduler\Enums\ScheduledJobRunStatus;
use App\Domains\Scheduler\Enums\ScheduledJobType;
use App\Domains\Scheduler\Jobs\ExecuteScheduledJob;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Domains\Scheduler\Models\ScheduledJobRun;
use App\Domains\Scheduler\Repositories\ScheduledJobRepository;
use App\Domains\Scheduler\Repositories\ScheduledJobRunRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Services\Sync\Scheduler as CronScheduler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SchedulerEngineService
{
    public function __construct(
        private readonly ScheduledJobRepository $jobRepository,
        private readonly ScheduledJobRunRepository $runRepository,
        private readonly ScheduledJobHandlerExecutor $handlerExecutor,
        private readonly CronScheduler $cronScheduler,
    ) {}

    /**
     * @return array{processed: int, results: list<array<string, mixed>>}
     */
    public function processDueJobs(int $limit = 50): array
    {
        $results = [];
        $processed = 0;

        foreach ($this->jobRepository->dueJobs($limit) as $job) {
            try {
                $results[] = $this->dispatchJob($job, trigger: 'schedule');
                $processed++;
            } catch (Throwable $exception) {
                Log::warning('Scheduler due job failed to dispatch', [
                    'job' => $job->uuid,
                    'error' => $exception->getMessage(),
                ]);
                $results[] = [
                    'job_uuid' => $job->uuid,
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return ['processed' => $processed, 'results' => $results];
    }

    /**
     * @return array<string, mixed>
     */
    public function runNow(ScheduledJob $job, ?User $actor = null): array
    {
        return $this->dispatchJob($job, trigger: 'manual', actor: $actor, force: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function retryRun(ScheduledJobRun $run, ?User $actor = null): array
    {
        $run->loadMissing('job');
        if (! $run->job) {
            throw new ApiException('Parent scheduled job not found.', 404);
        }

        return $this->dispatchJob($run->job, trigger: 'retry', actor: $actor, force: true, attempt: ((int) $run->attempt) + 1);
    }

    public function executeRun(string $runUuid): void
    {
        $run = $this->runRepository->findByIdentifierOrFail($runUuid)->load('job');
        $job = $run->job;
        if (! $job) {
            return;
        }

        $lock = null;
        if ($job->without_overlapping) {
            $lock = Cache::lock('scheduler-job-'.$job->uuid, max(60, (int) ($job->timeout_seconds ?? 300)));
            if (! $lock->get()) {
                $this->runRepository->update($run->id, [
                    'status' => ScheduledJobRunStatus::Cancelled->value,
                    'error_message' => 'Skipped due to overlapping execution.',
                    'finished_at' => now(),
                ]);

                return;
            }
        }

        $started = now();
        $this->runRepository->update($run->id, [
            'status' => ScheduledJobRunStatus::Running->value,
            'started_at' => $started,
        ]);
        $this->jobRepository->update($job->id, [
            'last_status' => ScheduledJobRunStatus::Running->value,
            'last_run_at' => $started,
        ]);

        try {
            $result = $this->handlerExecutor->execute($job, $run->fresh());
            $success = ($result['status'] ?? '') === 'success';
            $finished = now();

            $this->runRepository->update($run->id, [
                'status' => $success ? ScheduledJobRunStatus::Success->value : ScheduledJobRunStatus::Failed->value,
                'result' => $result,
                'error_message' => $success ? null : ($result['message'] ?? 'Job failed.'),
                'finished_at' => $finished,
                'duration_ms' => (int) $started->diffInMilliseconds($finished),
            ]);

            $this->jobRepository->update($job->id, [
                'last_status' => $success ? ScheduledJobRunStatus::Success->value : ScheduledJobRunStatus::Failed->value,
                'last_run_at' => $finished,
                'next_run_at' => $this->advanceSchedule($job),
            ]);
        } catch (Throwable $exception) {
            $finished = now();
            $this->runRepository->update($run->id, [
                'status' => ScheduledJobRunStatus::Failed->value,
                'error_message' => $exception->getMessage(),
                'finished_at' => $finished,
                'duration_ms' => (int) $started->diffInMilliseconds($finished),
            ]);
            $this->jobRepository->update($job->id, [
                'last_status' => ScheduledJobRunStatus::Failed->value,
                'last_run_at' => $finished,
                'next_run_at' => $this->advanceSchedule($job),
            ]);
        } finally {
            optional($lock)->release();
        }
    }

    public function computeNextRun(ScheduledJob $job): ?Carbon
    {
        $type = $job->job_type instanceof ScheduledJobType ? $job->job_type : ScheduledJobType::from((string) $job->job_type);

        return match ($type) {
            ScheduledJobType::Cron, ScheduledJobType::Recurring => $this->nextFromCron($job->schedule_cron, $job->timezone),
            ScheduledJobType::OneTime => $job->run_at && $job->run_at->isFuture() ? $job->run_at : null,
            ScheduledJobType::Delayed => now()->addMinutes(max(1, (int) ($job->delay_minutes ?? 1))),
            ScheduledJobType::Queue => $job->run_at && $job->run_at->isFuture()
                ? $job->run_at
                : ($job->schedule_cron ? $this->nextFromCron($job->schedule_cron, $job->timezone) : now()),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function dispatchJob(
        ScheduledJob $job,
        string $trigger,
        ?User $actor = null,
        bool $force = false,
        int $attempt = 1,
    ): array {
        if (! $force && ! $job->is_enabled) {
            throw new ApiException('Scheduled job is disabled.', 422);
        }

        $shouldExecuteSync = true;
        $run = DB::transaction(function () use ($job, $trigger, $actor, $attempt, &$shouldExecuteSync): ScheduledJobRun {
            /** @var ScheduledJobRun $run */
            $run = $this->runRepository->create([
                'scheduled_job_id' => $job->id,
                'status' => ScheduledJobRunStatus::Queued->value,
                'trigger' => $trigger,
                'attempt' => $attempt,
                'queue_name' => $job->queue_name ?: 'default',
                'payload' => $job->payload,
                'triggered_by' => $actor?->id,
            ]);

            $type = $job->job_type instanceof ScheduledJobType ? $job->job_type : ScheduledJobType::from((string) $job->job_type);

            if (in_array($type, [ScheduledJobType::OneTime, ScheduledJobType::Delayed], true) && $trigger !== 'retry') {
                $this->jobRepository->update($job->id, [
                    'next_run_at' => null,
                    'is_enabled' => $type === ScheduledJobType::Delayed ? false : (bool) $job->is_enabled,
                ]);
            } elseif ($trigger === 'schedule') {
                $this->jobRepository->update($job->id, [
                    'next_run_at' => $this->advanceSchedule($job, reserveOnly: true),
                ]);
            }

            $shouldExecuteSync = ! ($type === ScheduledJobType::Queue || ($job->payload['async'] ?? false) === true);

            if (! $shouldExecuteSync) {
                ExecuteScheduledJob::dispatch($run->uuid)->onQueue($job->queue_name ?: 'default');
            }

            return $run;
        });

        if ($shouldExecuteSync) {
            $this->executeRun($run->uuid);
        }

        return [
            'job_uuid' => $job->uuid,
            'run_uuid' => $run->uuid,
            'status' => 'dispatched',
        ];
    }

    private function advanceSchedule(ScheduledJob $job, bool $reserveOnly = false): ?Carbon
    {
        $type = $job->job_type instanceof ScheduledJobType ? $job->job_type : ScheduledJobType::from((string) $job->job_type);

        if (in_array($type, [ScheduledJobType::OneTime, ScheduledJobType::Delayed], true)) {
            return null;
        }

        if (blank($job->schedule_cron)) {
            return $reserveOnly ? null : $job->next_run_at;
        }

        return $this->nextFromCron($job->schedule_cron, $job->timezone);
    }

    private function nextFromCron(?string $cron, ?string $timezone = 'UTC'): ?Carbon
    {
        if (blank($cron)) {
            return null;
        }

        $next = $this->cronScheduler->nextRunDate($cron);
        if (! $next) {
            return null;
        }

        // CronExpression returns server-local; store UTC.
        return $next->utc();
    }
}
