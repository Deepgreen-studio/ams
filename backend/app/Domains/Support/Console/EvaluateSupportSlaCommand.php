<?php

namespace App\Domains\Support\Console;

use App\Domains\Support\Services\SupportSlaTrackingService;
use Illuminate\Console\Command;

class EvaluateSupportSlaCommand extends Command
{
    protected $signature = 'support:evaluate-sla {--limit=200 : Max tickets to evaluate}';

    protected $description = 'Evaluate open support ticket SLA timers and escalate breaches';

    public function handle(SupportSlaTrackingService $trackingService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $count = $trackingService->evaluateOpenTickets(null, $limit);
        $this->info("Evaluated {$count} support ticket(s).");

        return self::SUCCESS;
    }
}
