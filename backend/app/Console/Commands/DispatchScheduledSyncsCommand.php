<?php

namespace App\Console\Commands;

use App\Domains\Integrations\Services\IntegrationSyncService;
use Illuminate\Console\Command;

class DispatchScheduledSyncsCommand extends Command
{
    protected $signature = 'sync:dispatch-scheduled';

    protected $description = 'Dispatch due scheduled integration sync runs';

    public function handle(IntegrationSyncService $syncService): int
    {
        $count = $syncService->dispatchDueSchedules();
        $this->info("Dispatched {$count} scheduled sync run(s).");

        return self::SUCCESS;
    }
}
