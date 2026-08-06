<?php

namespace App\Domains\Workflows\Console;

use App\Domains\Workflows\Services\WorkflowEngineService;
use Illuminate\Console\Command;

class ProcessWorkflowTimeoutsCommand extends Command
{
    protected $signature = 'workflows:process-timeouts {--limit=50 : Max due instances to process}';

    protected $description = 'Process timed-out workflow steps and apply escalation rules';

    public function handle(WorkflowEngineService $engineService): int
    {
        $processed = $engineService->processTimeouts((int) $this->option('limit'));
        $this->info("Processed {$processed} workflow timeout(s).");

        return self::SUCCESS;
    }
}
