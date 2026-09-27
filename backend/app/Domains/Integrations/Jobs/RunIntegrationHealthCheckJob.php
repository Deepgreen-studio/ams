<?php

namespace App\Domains\Integrations\Jobs;

use App\Domains\Integrations\Services\ConnectorFrameworkService;
use App\Shared\Services\Queue\Middleware\TrackQueuedJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunIntegrationHealthCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public int $timeout;

    public ?string $trackUuid = null;

    public function __construct(
        public readonly int $integrationId,
    ) {
        $this->tries = (int) config('ams_queue.defaults.tries', 3);
        $this->timeout = (int) config('ams_queue.defaults.timeout', 90);
        $this->backoff = config('ams_queue.defaults.backoff', [10, 30, 60]);
        $this->onQueue('low');
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
        return 'monitoring';
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
            'integration_id' => $this->integrationId,
            'related_type' => 'integration',
            'related_id' => $this->integrationId,
        ];
    }

    public function handle(ConnectorFrameworkService $framework): void
    {
        $framework->checkHealth($this->integrationId);
    }
}
