<?php

namespace App\Domains\Scheduler\Console;

use App\Domains\Scheduler\Services\SchedulerEngineService;
use Illuminate\Console\Command;

class ProcessScheduledJobsCommand extends Command
{
    protected $signature = 'scheduler:process {--limit=50 : Max due jobs to process}';

    protected $description = 'Process due enterprise scheduler jobs';

    public function handle(SchedulerEngineService $engineService): int
    {
        $result = $engineService->processDueJobs((int) $this->option('limit'));
        $this->info('Processed '.$result['processed'].' scheduled job(s).');

        return self::SUCCESS;
    }
}
