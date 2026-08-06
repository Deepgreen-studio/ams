<?php

namespace App\Domains\Queue\Jobs;

use App\Shared\Services\NotificationManager;
use App\Shared\Services\Queue\Middleware\TrackQueuedJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public int $timeout;

    public ?string $trackUuid = null;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $channel,
        public readonly array $payload = [],
        public readonly ?int $companyId = null,
        public readonly ?int $actorId = null,
    ) {
        $this->tries = (int) config('ams_queue.defaults.tries', 3);
        $this->timeout = (int) config('ams_queue.defaults.timeout', 90);
        $this->backoff = config('ams_queue.defaults.backoff', [10, 30, 60]);
        $this->onQueue(config('ams_queue.types.notification.queue', 'notifications'));
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [app(TrackQueuedJob::class)];
    }

    public function queueType(): string
    {
        return 'notification';
    }

    public function queuePriority(): string
    {
        return 'low';
    }

    /**
     * @return array<string, mixed>
     */
    public function trackPayload(): array
    {
        return [
            'channel' => $this->channel,
            'payload' => $this->payload,
            'company_id' => $this->companyId,
            'triggered_by' => $this->actorId,
        ];
    }

    public function handle(NotificationManager $notificationManager): void
    {
        $matrix = $notificationManager->channelMatrix([$this->channel => true]);
        Log::info('ProcessNotificationJob handled', [
            'channel' => $this->channel,
            'matrix' => $matrix,
            'payload' => $this->payload,
        ]);
    }
}
