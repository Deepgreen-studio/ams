<?php

namespace App\Domains\Automation\Console;

use App\Domains\Automation\Services\AutomationEngineService;
use Illuminate\Console\Command;

class ProcessAutomationRulesCommand extends Command
{
    protected $signature = 'automation:process {--limit=50 : Max due rules to process}';

    protected $description = 'Process due scheduled and delayed automation rules';

    public function handle(AutomationEngineService $engineService): int
    {
        $result = $engineService->processDueRules((int) $this->option('limit'));
        $this->info('Processed '.$result['processed'].' automation rule(s).');

        return self::SUCCESS;
    }
}
