<?php

namespace App\Domains\Queue\Jobs;

use App\Domains\Integrations\Services\IntegrationSyncService;
use App\Shared\Services\Queue\Middleware\TrackQueuedJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public int $timeout;

    public ?string $trackUuid = null;

    public function __construct(
        public readonly int $syncRunId,
        public readonly ?int $companyId = null,
        public readonly ?int $actorId = null,
    ) {
        $this->tries = (int) config('ams_queue.defaults.tries', 3);
        $this->timeout = (int) config('ams_queue.defaults.timeout', 90);
        $this->backoff = config('ams_queue.defaults.backoff', [10, 30, 60]);
        $this->onQueue(config('ams_queue.types.import.queue', 'imports'));
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
        return 'import';
    }

    public function queuePriority(): string
    {
        return 'high';
    }

    /**
     * @return array<string, mixed>
     */
    public function trackPayload(): array
    {
        return [
            'sync_run_id' => $this->syncRunId,
            'company_id' => $this->companyId,
            'triggered_by' => $this->actorId,
            'related_type' => 'sync_run',
            'related_id' => $this->syncRunId,
        ];
    }

    public function handle(IntegrationSyncService $syncService): void
    {
        $syncService->processRun($this->syncRunId);
    }
}
