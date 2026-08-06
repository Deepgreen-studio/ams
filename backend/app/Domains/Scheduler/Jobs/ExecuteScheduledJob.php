<?php

namespace App\Domains\Scheduler\Jobs;

use App\Domains\Scheduler\Services\SchedulerEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteScheduledJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $runUuid,
    ) {}

    public function handle(SchedulerEngineService $engineService): void
    {
        $engineService->executeRun($this->runUuid);
    }
}
