<?php

namespace App\Console\Commands;

use App\Shared\Services\Monitoring\HealthMonitor;
use Illuminate\Console\Command;

class CaptureMonitoringSnapshotCommand extends Command
{
    protected $signature = 'monitoring:capture {--company=} {--integration=}';

    protected $description = 'Capture Integration Hub health snapshot and evaluate alerts';

    public function handle(HealthMonitor $healthMonitor): int
    {
        $companyId = $this->option('company') ? (int) $this->option('company') : null;
        $integrationId = $this->option('integration') ? (int) $this->option('integration') : null;

        $result = $healthMonitor->captureSnapshot($companyId, $integrationId);

        $this->info(sprintf(
            'Snapshot captured. Health=%d Performance=%d Alerts=%d',
            $result['metrics']['health_score'],
            $result['metrics']['performance_score'],
            count($result['events']),
        ));

        return self::SUCCESS;
    }
}
