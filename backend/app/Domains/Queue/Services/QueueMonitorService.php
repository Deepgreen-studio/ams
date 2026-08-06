<?php

namespace App\Domains\Queue\Services;

use App\Domains\Queue\Jobs\ProcessNotificationJob;
use App\Domains\Queue\Repositories\FailedJobRepository;
use App\Domains\Queue\Repositories\PendingJobRepository;
use App\Domains\Queue\Repositories\QueueJobTrackRepository;
use App\Models\User;
use App\Shared\Services\Queue\QueueManager;
use App\Shared\Services\Queue\QueueMonitor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class QueueMonitorService
{
    public function __construct(
        private readonly QueueManager $queueManager,
        private readonly QueueMonitor $queueMonitor,
        private readonly QueueJobTrackRepository $trackRepository,
        private readonly FailedJobRepository $failedJobRepository,
        private readonly PendingJobRepository $pendingJobRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $pending = $this->pendingJobRepository->counts();
        $failed = $this->failedJobRepository->count();
        $statusCounts = $this->trackRepository->statusCounts();
        $typeCounts = $this->trackRepository->typeCounts();

        return [
            'connection' => config('queue.default'),
            'worker_queues' => config('ams_queue.worker_queues', []),
            'queue_sizes' => $this->queueManager->sizesByQueue(),
            'pending' => $pending,
            'failed_count' => $failed,
            'tracks' => $statusCounts,
            'by_type' => $typeCounts,
            'recent_failed' => $this->failedJobRepository->recent(6)->map(fn ($job) => $this->transformFailed($job))->values()->all(),
            'recent_tracks' => $this->trackRepository->paginateFiltered(['per_page' => 8])->items(),
            'status' => $this->queueManager->status(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listTracks(array $filters = []): LengthAwarePaginator
    {
        return $this->trackRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listRunning(array $filters = []): LengthAwarePaginator
    {
        $filters['status'] = 'running';

        return $this->trackRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listPendingJobs(array $filters = []): LengthAwarePaginator
    {
        $filters['pending_only'] = true;

        return $this->pendingJobRepository->paginate($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listFailed(array $filters = []): LengthAwarePaginator
    {
        return $this->failedJobRepository->paginate($filters);
    }

    public function showFailed(string $uuid): array
    {
        return $this->transformFailed($this->failedJobRepository->findByUuidOrFail($uuid), detailed: true);
    }

    public function retryFailed(string $uuid, ?User $actor = null): array
    {
        $job = $this->failedJobRepository->findByUuidOrFail($uuid);
        $this->queueManager->retryFailed($uuid);

        return [
            'uuid' => $job->uuid,
            'retried' => true,
            'actor' => $actor?->uuid,
        ];
    }

    public function retryAllFailed(?User $actor = null): array
    {
        $count = $this->queueManager->retryAllFailed();

        return ['retried' => $count, 'actor' => $actor?->uuid];
    }

    public function forgetFailed(string $uuid): void
    {
        $this->failedJobRepository->findByUuidOrFail($uuid);
        $this->queueManager->forgetFailed($uuid);
    }

    public function flushFailed(?User $actor = null): array
    {
        $count = $this->queueManager->flushFailed();

        return ['flushed' => $count, 'actor' => $actor?->uuid];
    }

    public function restartWorkers(?User $actor = null): array
    {
        $this->queueManager->restartWorkers();

        return [
            'restarted' => true,
            'message' => 'Queue workers will restart gracefully after finishing their current job.',
            'actor' => $actor?->uuid,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function statistics(): array
    {
        $pending = $this->pendingJobRepository->counts();
        $queueSizes = $this->queueManager->sizesByQueue();

        return [
            'connection' => config('queue.default'),
            'queue_sizes' => $queueSizes,
            'pending_jobs' => $pending,
            'failed_jobs' => $this->failedJobRepository->count(),
            'track_status' => $this->trackRepository->statusCounts(),
            'track_types' => $this->trackRepository->typeCounts(),
            'jobs_last_24h' => [
                'completed' => $this->trackRepository->filteredQuery([
                    'status' => 'completed',
                ])->where('finished_at', '>=', now()->subDay())->count(),
                'failed' => $this->trackRepository->filteredQuery([
                    'status' => 'failed',
                ])->where('failed_at', '>=', now()->subDay())->count(),
            ],
            'database_jobs_table' => DB::table('jobs')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function dispatchNotificationSample(array $payload, User $actor): array
    {
        $delay = (int) ($payload['delay_seconds'] ?? 0);
        $priority = (string) ($payload['priority'] ?? 'low');
        $job = new ProcessNotificationJob(
            channel: (string) ($payload['channel'] ?? 'in_app'),
            payload: (array) ($payload['payload'] ?? ['message' => 'Queue sample notification']),
            companyId: isset($payload['company_id']) ? (int) $payload['company_id'] : null,
            actorId: $actor->id,
        );

        $queue = $this->queueManager->resolveQueue('notification', $priority);
        $job->onQueue($queue);
        if ($delay > 0) {
            $job->delay(now()->addSeconds($delay));
        }

        $track = $this->queueMonitor->recordQueued([
            'job_class' => ProcessNotificationJob::class,
            'display_name' => 'ProcessNotificationJob',
            'queue' => $queue,
            'priority' => $priority,
            'type' => 'notification',
            'max_tries' => $job->tries,
            'delay_seconds' => $delay,
            'payload' => $job->trackPayload(),
            'triggered_by' => $actor->id,
        ]);
        $job->trackUuid = $track->uuid;

        dispatch($job);

        return [
            'track' => $track->fresh(),
            'queue' => $queue,
            'delay_seconds' => $delay,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function transformFailed(object $job, bool $detailed = false): array
    {
        $payload = json_decode((string) $job->payload, true) ?: [];
        $displayName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'UnknownJob');

        $data = [
            'id' => $job->id,
            'uuid' => $job->uuid,
            'connection' => $job->connection,
            'queue' => $job->queue,
            'display_name' => $displayName,
            'failed_at' => $job->failed_at,
        ];

        if ($detailed) {
            $data['payload'] = $payload;
            $data['exception'] = $job->exception;
        } else {
            $data['exception'] = \Illuminate\Support\Str::limit((string) $job->exception, 240);
        }

        return $data;
    }
}
