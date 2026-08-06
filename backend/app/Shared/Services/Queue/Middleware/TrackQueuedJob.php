<?php

namespace App\Shared\Services\Queue\Middleware;

use App\Domains\Queue\Models\QueueJobTrack;
use App\Shared\Services\Queue\QueueMonitor;
use Closure;
use Illuminate\Support\Str;
use Throwable;

class TrackQueuedJob
{
    public function __construct(
        private readonly QueueMonitor $monitor,
    ) {}

    /**
     * @param  object  $job
     */
    public function handle(object $job, Closure $next): mixed
    {
        $track = $this->resolveOrCreateTrack($job);

        try {
            $this->monitor->markRunning($track, $this->jobUuid($job));
            $result = $next($job);
            $this->monitor->markCompleted($track->fresh() ?? $track);

            return $result;
        } catch (Throwable $e) {
            $this->monitor->markFailed($track->fresh() ?? $track, $e->getMessage()."\n".$e->getTraceAsString());
            throw $e;
        }
    }

    protected function resolveOrCreateTrack(object $job): QueueJobTrack
    {
        if (property_exists($job, 'trackUuid') && filled($job->trackUuid)) {
            $existing = QueueJobTrack::query()->where('uuid', $job->trackUuid)->first();
            if ($existing) {
                return $existing;
            }
        }

        $type = method_exists($job, 'queueType') ? (string) $job->queueType() : 'default';
        $priority = method_exists($job, 'queuePriority') ? (string) $job->queuePriority() : 'normal';
        $payload = method_exists($job, 'trackPayload') ? (array) $job->trackPayload() : [];

        $track = $this->monitor->recordQueued([
            'job_class' => $job::class,
            'display_name' => class_basename($job),
            'queue' => property_exists($job, 'queue') ? (string) ($job->queue ?: 'default') : 'default',
            'priority' => $priority,
            'type' => $type,
            'max_tries' => property_exists($job, 'tries') ? (int) $job->tries : (int) config('ams_queue.defaults.tries', 3),
            'delay_seconds' => 0,
            'payload' => $payload,
            'company_id' => $payload['company_id'] ?? null,
            'triggered_by' => $payload['triggered_by'] ?? null,
            'related_type' => $payload['related_type'] ?? null,
            'related_id' => $payload['related_id'] ?? null,
        ]);

        if (property_exists($job, 'trackUuid')) {
            $job->trackUuid = $track->uuid;
        }

        return $track;
    }

    protected function jobUuid(object $job): ?string
    {
        try {
            if (method_exists($job, 'job') && $job->job) {
                return $job->job->uuid();
            }
        } catch (Throwable) {
            //
        }

        return (string) Str::uuid();
    }
}
