<?php

namespace App\Shared\Services\Queue;

use App\Domains\Queue\Models\QueueJobTrack;
use App\Domains\Queue\Repositories\QueueJobTrackRepository;
use Illuminate\Support\Str;

class QueueMonitor
{
    public function __construct(
        private readonly QueueJobTrackRepository $trackRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordQueued(array $attributes): QueueJobTrack
    {
        return $this->trackRepository->createTrack(array_merge([
            'uuid' => (string) Str::uuid(),
            'status' => 'queued',
            'queued_at' => now(),
            'available_at' => now()->addSeconds((int) ($attributes['delay_seconds'] ?? 0)),
            'attempts' => 0,
        ], $attributes));
    }

    public function markRunning(QueueJobTrack $track, ?string $jobUuid = null): QueueJobTrack
    {
        return $this->trackRepository->updateTrack($track, array_filter([
            'status' => 'running',
            'started_at' => now(),
            'job_uuid' => $jobUuid,
            'attempts' => ((int) $track->attempts) + 1,
        ], fn ($value) => $value !== null));
    }

    public function markCompleted(QueueJobTrack $track): QueueJobTrack
    {
        return $this->trackRepository->updateTrack($track, [
            'status' => 'completed',
            'finished_at' => now(),
            'exception' => null,
        ]);
    }

    public function markFailed(QueueJobTrack $track, string $exception): QueueJobTrack
    {
        return $this->trackRepository->updateTrack($track, [
            'status' => 'failed',
            'failed_at' => now(),
            'finished_at' => now(),
            'exception' => Str::limit($exception, 5000, ''),
        ]);
    }
}
