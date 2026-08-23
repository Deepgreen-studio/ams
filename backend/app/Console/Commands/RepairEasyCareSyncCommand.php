<?php

namespace App\Console\Commands;

use App\Domains\Integrations\Enums\IntegrationAuthenticationType;
use App\Domains\Integrations\Enums\IntegrationHealthStatus;
use App\Domains\Integrations\Enums\IntegrationStatus;
use App\Domains\Integrations\Enums\SyncConflictStrategy;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Models\SyncConfig;
use App\Domains\Integrations\Services\IntegrationSyncService;
use App\Models\User;
use Illuminate\Console\Command;

class RepairEasyCareSyncCommand extends Command
{
    protected $signature = 'sync:repair-easycare
                            {--run : Execute the patients import after repairing the connection}';

    protected $description = 'Point the EasyCare integration at a reachable API host and optionally run patient import';

    public function handle(IntegrationSyncService $syncService): int
    {
        $baseUrl = rtrim((string) env('EASYCARE_API_BASE_URL', 'http://easycare-api.test'), '/').'/';
        $token = (string) env('EASYCARE_API_TOKEN', '');

        $integration = Integration::query()->where('slug', 'easycare-api')->first();
        if ($integration === null) {
            $this->error('EasyCare integration (slug easycare-api) was not found. Seed EasyCareCompanySeeder first.');

            return self::FAILURE;
        }

        $credentials = is_array($integration->credentials) ? $integration->credentials : [];
        if ($token !== '') {
            $credentials['bearer_token'] = $token;
        }

        $integration->forceFill([
            'base_url' => $baseUrl,
            'status' => IntegrationStatus::Active,
            'authentication_type' => IntegrationAuthenticationType::BearerToken,
            'health_check_path' => '/api/v1/health',
            'health_status' => IntegrationHealthStatus::Unknown,
            'credentials' => $credentials,
        ])->save();

        $configs = SyncConfig::query()
            ->where('integration_id', $integration->id)
            ->where(function ($query): void {
                $query->where('source_path', 'like', '%patients%')
                    ->orWhere('slug', 'easycare-patients');
            })
            ->get();

        foreach ($configs as $config) {
            $config->forceFill([
                'is_enabled' => true,
                'entity_type' => 'patients',
                'conflict_strategy' => SyncConflictStrategy::Overwrite,
                'source_path' => $config->source_path ?: '/api/v1/patients',
            ])->save();
        }

        $this->info('EasyCare integration base URL: '.$integration->fresh()->base_url);
        $this->info('Bearer token configured: '.(filled($credentials['bearer_token'] ?? null) ? 'yes' : 'no'));
        $this->info('Patient sync configs updated: '.$configs->count());

        if (! $this->option('run')) {
            return self::SUCCESS;
        }

        $config = $configs->first();
        if ($config === null) {
            $this->error('No EasyCare patients sync config found.');

            return self::FAILURE;
        }

        $actor = User::query()->where('email', 'admin@ams.test')->first()
            ?? User::query()->role('super-admin')->first()
            ?? User::query()->orderBy('id')->first();

        if ($actor === null) {
            $this->error('No AMS user available to trigger the sync.');

            return self::FAILURE;
        }

        $result = $syncService->run($config->uuid, $actor, trigger: 'manual', mode: 'full', background: false);
        $run = $result['run']->fresh();

        $this->info('Run status: '.($run->status?->value ?? $run->status));
        $this->info('Imported: '.$run->imported.' · updated: '.$run->updated.' · failed: '.$run->failed.' · skipped: '.$run->skipped);

        if (filled($run->error_message)) {
            $this->error((string) $run->error_message);

            return self::FAILURE;
        }

        return ($run->status?->value ?? $run->status) === 'completed' ? self::SUCCESS : self::FAILURE;
    }
}
