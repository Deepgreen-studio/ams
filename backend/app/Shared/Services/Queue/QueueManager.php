<?php

namespace App\Shared\Services\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class QueueManager
{
    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        return [
            'default' => config('queue.default'),
            'connections' => array_keys(config('queue.connections', [])),
            'worker_queues' => config('ams_queue.worker_queues', []),
            'priorities' => config('ams_queue.priorities', []),
            'types' => config('ams_queue.types', []),
        ];
    }

    public function size(?string $queue = null): int
    {
        try {
            return Queue::size($queue);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array<string, int>
     */
    public function sizesByQueue(): array
    {
        $queues = config('ams_queue.worker_queues', ['default']);
        $sizes = [];
        foreach ($queues as $queue) {
            $sizes[$queue] = $this->size($queue);
        }

        return $sizes;
    }

    public function resolveQueue(?string $type = null, ?string $priority = null, ?string $queue = null): string
    {
        if ($queue) {
            return $queue;
        }

        if ($priority && $priority !== 'normal') {
            $priorityQueue = config("ams_queue.priorities.{$priority}");
            if (is_string($priorityQueue) && $priorityQueue !== '') {
                return $priorityQueue;
            }
        }

        if ($type) {
            $configured = config("ams_queue.types.{$type}.queue");
            if (is_string($configured) && $configured !== '') {
                return $configured;
            }
        }

        return (string) config('queue.connections.'.config('queue.default').'.queue', 'default');
    }

    /**
     * @param  class-string|ShouldQueue|object  $job
     */
    public function dispatch(
        object|string $job,
        ?string $type = null,
        ?string $priority = null,
        ?string $queue = null,
        int $delaySeconds = 0,
    ): mixed {
        $instance = is_string($job) ? app($job) : $job;
        $targetQueue = $this->resolveQueue($type, $priority, $queue);

        if (method_exists($instance, 'onQueue')) {
            $instance->onQueue($targetQueue);
        }

        if ($delaySeconds > 0 && method_exists($instance, 'delay')) {
            $instance->delay(now()->addSeconds($delaySeconds));
        }

        return dispatch($instance);
    }

    public function retryFailed(string $uuid): void
    {
        $failed = DB::table('failed_jobs')->where('uuid', $uuid)->first();
        if (! $failed) {
            abort(404, 'Failed job not found.');
        }

        try {
            Artisan::call('queue:retry', ['id' => [$uuid]]);
        } catch (\Throwable) {
            $this->requeueFailedRaw($failed);
        }

        // Artisan may leave the row when payload cannot be hydrated; ensure cleanup via raw path.
        if (DB::table('failed_jobs')->where('uuid', $uuid)->exists()) {
            $this->requeueFailedRaw($failed);
        }
    }

    public function retryAllFailed(): int
    {
        $failed = DB::table('failed_jobs')->orderBy('id')->get();
        foreach ($failed as $job) {
            $this->retryFailed($job->uuid);
        }

        return $failed->count();
    }

    public function flushFailed(): int
    {
        $count = DB::table('failed_jobs')->count();
        Artisan::call('queue:flush');

        return $count;
    }

    public function restartWorkers(): void
    {
        Artisan::call('queue:restart');
    }

    public function forgetFailed(string $uuid): void
    {
        if (! DB::table('failed_jobs')->where('uuid', $uuid)->exists()) {
            abort(404, 'Failed job not found.');
        }

        Artisan::call('queue:forget', ['id' => $uuid]);
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();
    }

    protected function requeueFailedRaw(object $failed): void
    {
        DB::table('jobs')->insert([
            'queue' => $failed->queue,
            'payload' => $failed->payload,
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->getTimestamp(),
            'created_at' => now()->getTimestamp(),
        ]);

        DB::table('failed_jobs')->where('uuid', $failed->uuid)->delete();
    }
}
