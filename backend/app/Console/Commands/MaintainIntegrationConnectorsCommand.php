<?php

namespace App\Console\Commands;

use App\Domains\Integrations\Services\ConnectorFrameworkService;
use Illuminate\Console\Command;

class MaintainIntegrationConnectorsCommand extends Command
{
    protected $signature = 'integrations:maintain {--sync : Also dispatch due sync schedules} {--skip-health : Skip queued health checks} {--skip-oauth : Skip OAuth token refresh} {--skip-retries : Skip due webhook retries}';

    protected $description = 'Run connector maintenance: OAuth refresh, webhook retries, optional sync, and health checks';

    public function handle(ConnectorFrameworkService $framework): int
    {
        $result = $framework->maintain([
            'oauth' => ! $this->option('skip-oauth'),
            'retries' => ! $this->option('skip-retries'),
            'sync' => (bool) $this->option('sync'),
            'health' => ! $this->option('skip-health'),
        ]);

        $this->info(sprintf(
            'Connectors maintained. OAuth refreshed: %d. Webhook retries: %d. Sync runs: %d. Health checks queued: %d.',
            $result['oauth_refreshed'],
            $result['webhook_retries'],
            $result['sync_runs'],
            $result['health_checks'],
        ));

        return self::SUCCESS;
    }
}
