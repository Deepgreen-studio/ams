<?php

namespace App\Domains\Scheduler\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Scheduler\Enums\ScheduledJobHandler;
use App\Domains\Scheduler\Enums\ScheduledJobType;
use App\Domains\Scheduler\Events\ScheduledJobCreated;
use App\Domains\Scheduler\Events\ScheduledJobDeleted;
use App\Domains\Scheduler\Events\ScheduledJobUpdated;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Domains\Scheduler\Models\ScheduledJobRun;
use App\Domains\Scheduler\Repositories\ScheduledJobLogRepository;
use App\Domains\Scheduler\Repositories\ScheduledJobRepository;
use App\Domains\Scheduler\Repositories\ScheduledJobRunRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Services\Sync\Scheduler as CronScheduler;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ScheduledJobService
{
    public function __construct(
        private readonly ScheduledJobRepository $jobRepository,
        private readonly ScheduledJobRunRepository $runRepository,
        private readonly ScheduledJobLogRepository $logRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly SchedulerEngineService $engineService,
        private readonly CronScheduler $cronScheduler,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['company_id']) && ! is_numeric($filters['company_id'])) {
            $filters['company_id'] = $this->companyRepository
                ->findByIdentifierOrFail((string) $filters['company_id'])->id;
        }

        return $this->jobRepository->paginateFiltered($filters);
    }

    public function find(string $identifier): ScheduledJob
    {
        return $this->jobRepository->findByIdentifierOrFail($identifier)
            ->load(['company:id,uuid,company_name', 'creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        return [
            'statistics' => $this->jobRepository->statistics(),
            'run_statistics' => $this->runRepository->statistics(),
            'catalog' => $this->catalog(),
            'recent_runs' => $this->runRepository->paginateFiltered(['per_page' => 8])->items(),
            'recent_failed' => $this->runRepository->paginateFiltered([
                'status' => 'failed',
                'per_page' => 5,
            ])->items(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        return [
            'job_types' => collect(ScheduledJobType::cases())->map(fn ($item) => [
                'value' => $item->value,
                'label' => $item->label(),
            ])->values()->all(),
            'handlers' => collect(ScheduledJobHandler::cases())->map(fn ($item) => [
                'value' => $item->value,
                'label' => $item->label(),
                'description' => $item->description(),
                'default_cron' => $item->defaultCron(),
            ])->values()->all(),
            'common_cron' => $this->cronScheduler->commonExpressions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ScheduledJob
    {
        return DB::transaction(function () use ($data, $actor): ScheduledJob {
            $payload = $this->preparePayload($data);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            /** @var ScheduledJob $job */
            $job = $this->jobRepository->create($payload);
            $next = $this->engineService->computeNextRun($job->fresh());
            if ($next) {
                $this->jobRepository->update($job->id, ['next_run_at' => $next]);
            }

            $fresh = $this->find($job->uuid);
            event(new ScheduledJobCreated($fresh, $actor));

            return $fresh;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): ScheduledJob
    {
        return DB::transaction(function () use ($identifier, $data, $actor): ScheduledJob {
            $job = $this->jobRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            /** @var ScheduledJob $updated */
            $updated = $this->jobRepository->update($job->id, $payload);
            $next = $this->engineService->computeNextRun($updated->fresh());
            $this->jobRepository->update($updated->id, ['next_run_at' => $next]);

            $fresh = $this->find($updated->uuid);
            event(new ScheduledJobUpdated($fresh, $actor));

            return $fresh;
        });
    }

    public function toggle(string $identifier, User $actor, ?bool $enabled = null): ScheduledJob
    {
        $job = $this->jobRepository->findByIdentifierOrFail($identifier);
        $next = $enabled ?? ! $job->is_enabled;

        /** @var ScheduledJob $updated */
        $updated = $this->jobRepository->update($job->id, [
            'is_enabled' => $next,
            'updated_by' => $actor->id,
            'next_run_at' => $next ? $this->engineService->computeNextRun($job) : null,
        ]);

        event(new ScheduledJobUpdated($updated, $actor));

        return $this->find($updated->uuid);
    }

    public function delete(string $identifier, User $actor): void
    {
        $job = $this->jobRepository->findByIdentifierOrFail($identifier);
        $this->jobRepository->delete($job->id);
        event(new ScheduledJobDeleted($job, $actor));
    }

    /**
     * @return array<string, mixed>
     */
    public function runNow(string $identifier, User $actor): array
    {
        $job = $this->find($identifier);

        return $this->engineService->runNow($job, $actor);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateRuns(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['job'])) {
            $job = $this->jobRepository->findByIdentifierOrFail((string) $filters['job']);
            $filters['scheduled_job_id'] = $job->id;
        }

        return $this->runRepository->paginateFiltered($filters);
    }

    public function findRun(string $identifier): ScheduledJobRun
    {
        return $this->runRepository->findByIdentifierOrFail($identifier)
            ->load([
                'job:id,uuid,name,handler_key,job_type,queue_name',
                'triggerer:id,uuid,full_name,email',
                'logs',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function retryRun(string $identifier, User $actor): array
    {
        $run = $this->findRun($identifier);

        return $this->engineService->retryRun($run, $actor);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateLogs(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['run'])) {
            $run = $this->runRepository->findByIdentifierOrFail((string) $filters['run']);
            $filters['scheduled_job_run_id'] = $run->id;
        }

        return $this->logRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(array $data, bool $isUpdate = false): array
    {
        $payload = [];

        foreach ([
            'name', 'description', 'schedule_cron', 'timezone', 'run_at', 'delay_minutes',
            'queue_name', 'is_enabled', 'without_overlapping', 'max_attempts', 'timeout_seconds',
            'payload', 'metadata',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (array_key_exists('job_type', $data)) {
            $payload['job_type'] = ScheduledJobType::from((string) $data['job_type'])->value;
        } elseif (! $isUpdate) {
            $payload['job_type'] = ScheduledJobType::Cron->value;
        }

        if (array_key_exists('handler_key', $data)) {
            if (! ScheduledJobHandler::tryFrom((string) $data['handler_key'])) {
                throw new ApiException('Unsupported scheduler handler.', 422);
            }
            $payload['handler_key'] = $data['handler_key'];
        } elseif (! $isUpdate) {
            throw new ApiException('Handler key is required.', 422);
        }

        if (array_key_exists('company_id', $data)) {
            $payload['company_id'] = blank($data['company_id'])
                ? null
                : $this->companyRepository->findByIdentifierOrFail((string) $data['company_id'])->id;
        }

        $type = ScheduledJobType::from((string) ($payload['job_type'] ?? $data['job_type'] ?? ScheduledJobType::Cron->value));

        if (in_array($type, [ScheduledJobType::Cron, ScheduledJobType::Recurring], true)) {
            $cron = (string) ($payload['schedule_cron'] ?? $data['schedule_cron'] ?? '');
            if ($cron === '' && ! $isUpdate) {
                throw new ApiException('Cron expression is required for cron/recurring jobs.', 422);
            }
            if ($cron !== '' && ! \Cron\CronExpression::isValidExpression($cron)) {
                throw new ApiException('Invalid cron expression.', 422);
            }
        }

        if ($type === ScheduledJobType::OneTime && blank($payload['run_at'] ?? $data['run_at'] ?? null) && ! $isUpdate) {
            throw new ApiException('run_at is required for one-time jobs.', 422);
        }

        if ($type === ScheduledJobType::Delayed && blank($payload['delay_minutes'] ?? $data['delay_minutes'] ?? null) && ! $isUpdate) {
            throw new ApiException('delay_minutes is required for delayed jobs.', 422);
        }

        if (! array_key_exists('timezone', $payload) && ! $isUpdate) {
            $payload['timezone'] = 'UTC';
        }
        if (! array_key_exists('queue_name', $payload) && ! $isUpdate) {
            $payload['queue_name'] = 'default';
        }
        if (! array_key_exists('is_enabled', $payload) && ! $isUpdate) {
            $payload['is_enabled'] = true;
        }

        return $payload;
    }
}
