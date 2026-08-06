<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Integrations\Enums\SyncRunStatus;
use App\Domains\Integrations\Enums\SyncTrigger;
use App\Domains\Integrations\Events\SyncRunCompleted;
use App\Domains\Integrations\Events\SyncRunFailed;
use App\Domains\Integrations\Events\SyncRunStarted;
use App\Domains\Integrations\Jobs\RunIntegrationSyncJob;
use App\Domains\Integrations\Models\SyncConfig;
use App\Domains\Integrations\Models\SyncRun;
use App\Domains\Integrations\Repositories\IntegrationRepository;
use App\Domains\Integrations\Repositories\SyncConfigRepository;
use App\Domains\Integrations\Repositories\SyncLogRepository;
use App\Domains\Integrations\Repositories\SyncRunRepository;
use App\Domains\Queue\Jobs\ProcessExportJob;
use App\Domains\Queue\Jobs\ProcessImportJob;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use App\Shared\Services\Sync\Scheduler;
use App\Shared\Services\Sync\SyncService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IntegrationSyncService
{
    public function __construct(
        private readonly SyncConfigRepository $syncConfigRepository,
        private readonly SyncRunRepository $syncRunRepository,
        private readonly SyncLogRepository $syncLogRepository,
        private readonly IntegrationRepository $integrationRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly SyncService $syncService,
        private readonly Scheduler $scheduler,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listConfigs(array $filters = []): LengthAwarePaginator
    {
        $this->normalizeCompanyFilter($filters);

        return $this->syncConfigRepository->paginateFiltered($filters);
    }

    public function findConfig(string $identifier): SyncConfig
    {
        return $this->syncConfigRepository->findByIdentifierOrFail($identifier);
    }

    public function showConfig(string $identifier): SyncConfig
    {
        return $this->findConfig($identifier)->load([
            'company:id,uuid,company_name',
            'integration:id,uuid,name,slug,status,base_url,authentication_type',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createConfig(array $data, User $actor): SyncConfig
    {
        return DB::transaction(function () use ($data, $actor): SyncConfig {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $integration = $this->integrationRepository->findByIdentifierOrFail((string) $data['integration_id']);

            $payload = $this->prepareConfigPayload($data);
            $payload['company_id'] = $company->id;
            $payload['integration_id'] = $integration->id;
            $payload['slug'] = $this->uniqueSlug($company->id, $payload['slug'] ?? null, $payload['name']);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
            $payload['default_mode'] = $payload['default_mode'] ?? 'incremental';
            $payload['trigger_type'] = $payload['trigger_type'] ?? 'manual';
            $payload['conflict_strategy'] = $payload['conflict_strategy'] ?? 'skip';
            $payload['batch_size'] = $payload['batch_size'] ?? 100;
            $payload['is_enabled'] = $payload['is_enabled'] ?? true;
            $payload['entity_type'] = $payload['entity_type'] ?? 'generic';

            if (($payload['trigger_type'] ?? null) === 'scheduled' && blank($payload['schedule_cron'] ?? null)) {
                throw new ApiException('Scheduled sync requires a cron expression.', 422);
            }

            return $this->syncConfigRepository->createConfig($payload);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateConfig(string $identifier, array $data, User $actor): SyncConfig
    {
        return DB::transaction(function () use ($identifier, $data, $actor): SyncConfig {
            $config = $this->syncConfigRepository->findByIdentifierOrFail($identifier);
            $payload = $this->prepareConfigPayload($data, true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('integration_id', $data) && filled($data['integration_id'])) {
                $integration = $this->integrationRepository->findByIdentifierOrFail((string) $data['integration_id']);
                $payload['integration_id'] = $integration->id;
            }

            if (array_key_exists('slug', $payload) || array_key_exists('name', $payload)) {
                $payload['slug'] = $this->uniqueSlug(
                    $config->company_id,
                    $payload['slug'] ?? $config->slug,
                    $payload['name'] ?? $config->name,
                    $config->id
                );
            }

            $trigger = $payload['trigger_type'] ?? $config->trigger_type?->value;
            $cron = $payload['schedule_cron'] ?? $config->schedule_cron;
            if ($trigger === 'scheduled' && blank($cron)) {
                throw new ApiException('Scheduled sync requires a cron expression.', 422);
            }

            return $this->syncConfigRepository->updateConfig($config, $payload);
        });
    }

    public function deleteConfig(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $config = $this->syncConfigRepository->findByIdentifierOrFail($identifier);
            $this->syncConfigRepository->updateConfig($config, ['updated_by' => $actor->id]);
            $config->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{config: SyncConfig, run: SyncRun}
     */
    public function run(
        string $identifier,
        User $actor,
        string $trigger = 'manual',
        ?string $mode = null,
        bool $background = true,
        array $options = [],
    ): array {
        $config = $this->syncConfigRepository->findByIdentifierOrFail($identifier);
        if (! $config->is_enabled) {
            throw new ApiException('Sync configuration is disabled.', 422);
        }

        $resolvedTrigger = $background && $trigger === 'manual'
            ? SyncTrigger::Background->value
            : $trigger;

        if (! in_array($resolvedTrigger, SyncTrigger::values(), true)) {
            $resolvedTrigger = SyncTrigger::Manual->value;
        }

        $run = $this->syncRunRepository->createRun([
            'sync_config_id' => $config->id,
            'company_id' => $config->company_id,
            'integration_id' => $config->integration_id,
            'trigger' => $resolvedTrigger,
            'mode' => $mode ?: ($config->default_mode?->value ?? 'incremental'),
            'direction' => $config->direction?->value ?? 'import',
            'status' => SyncRunStatus::Queued->value,
            'meta' => $options,
            'triggered_by' => $actor->id,
        ]);

        event(new SyncRunStarted($config, $run, $actor));

        if (! $background || app()->environment('testing')) {
            $this->processRun($run->id);

            return [
                'config' => $config->fresh(['integration', 'company']),
                'run' => $this->syncRunRepository->findByUuidOrFail($run->uuid)->load(['config', 'integration', 'actor']),
            ];
        }

        $this->dispatchBackgroundSync($run);

        return [
            'config' => $config->load(['integration', 'company']),
            'run' => $run->fresh(['config', 'integration', 'actor']),
        ];
    }

    public function processRun(int $syncRunId): SyncRun
    {
        /** @var SyncRun|null $run */
        $run = SyncRun::query()->with(['config.integration'])->find($syncRunId);
        if (! $run || ! $run->config || ! $run->config->integration) {
            abort(404, 'Sync run not found.');
        }

        $config = $run->config;
        $integration = $config->integration;

        $this->syncRunRepository->updateRun($run, [
            'status' => SyncRunStatus::Running->value,
            'started_at' => now(),
            'progress_percent' => 5,
        ]);

        $this->writeLog($run, $config->id, 'info', 'sync', null, 'Sync run started.');

        $connection = [
            'base_url' => $integration->base_url,
            'default_headers' => $integration->default_headers ?? [],
            'default_query' => $integration->default_query ?? [],
            'timeout' => $integration->timeout,
            'retry_attempts' => $integration->retry_attempts,
            'rate_limit_per_minute' => $integration->rate_limit_per_minute,
            'rate_limit_key' => 'sync:'.$config->id,
            'authentication_type' => $integration->authentication_type?->value ?? $integration->authentication_type,
            'credentials' => is_array($integration->credentials) ? $integration->credentials : [],
        ];

        $engineConfig = [
            'direction' => $run->direction?->value ?? 'import',
            'mode' => $run->mode?->value ?? 'full',
            'source_path' => $config->source_path ?: '/',
            'target_path' => $config->target_path ?: '/',
            'batch_size' => $config->batch_size,
            'conflict_strategy' => $config->conflict_strategy?->value ?? 'skip',
            'field_mapping' => $config->field_mapping ?? [],
            'filters' => $config->filters ?? [],
            'cursor_field' => $config->cursor_field,
            'cursor_value' => $config->cursor_value,
            'record_snapshot' => $config->record_snapshot ?? [],
            'entity_type' => $config->entity_type,
            'timeout' => $integration->timeout,
            'retry_attempts' => $integration->retry_attempts,
            'key_field' => $config->options['key_field'] ?? 'id',
            'export_method' => $config->options['export_method'] ?? 'POST',
            'sample_records' => $config->options['sample_records'] ?? null,
            'sample_export' => (bool) ($config->options['sample_export'] ?? false),
        ];

        $result = $this->syncService->execute($connection, $engineConfig);

        foreach ($result->logs as $log) {
            $this->writeLog(
                $run,
                $config->id,
                (string) ($log['level'] ?? 'info'),
                isset($log['action']) ? (string) $log['action'] : null,
                isset($log['record_key']) ? (string) $log['record_key'] : null,
                (string) ($log['message'] ?? ''),
                (array) ($log['context'] ?? []),
            );
        }

        if ($result->successful) {
            $updated = $this->syncRunRepository->updateRun($run, [
                'status' => SyncRunStatus::Completed->value,
                'completed_at' => now(),
                'total_records' => $result->totalRecords,
                'imported' => $result->imported,
                'exported' => $result->exported,
                'updated' => $result->updated,
                'failed' => $result->failed,
                'skipped' => $result->skipped,
                'progress_percent' => 100,
                'error_message' => null,
                'meta' => array_merge((array) $run->meta, $result->meta),
            ]);

            $this->syncConfigRepository->updateConfig($config, [
                'record_snapshot' => $result->snapshot,
                'cursor_value' => $result->cursorValue,
                'last_synced_at' => now(),
            ]);

            event(new SyncRunCompleted($config->fresh(), $updated));

            return $updated;
        }

        $updated = $this->syncRunRepository->updateRun($run, [
            'status' => SyncRunStatus::Failed->value,
            'failed_at' => now(),
            'completed_at' => now(),
            'total_records' => $result->totalRecords,
            'imported' => $result->imported,
            'exported' => $result->exported,
            'updated' => $result->updated,
            'failed' => max(1, $result->failed),
            'skipped' => $result->skipped,
            'progress_percent' => $result->progressPercent(),
            'error_message' => $result->error ?: 'Sync failed.',
            'meta' => array_merge((array) $run->meta, $result->meta),
        ]);

        event(new SyncRunFailed($config->fresh(), $updated));

        return $updated;
    }

    /**
     * @return array{config: SyncConfig, run: SyncRun}
     */
    public function showRun(string $uuid): array
    {
        $run = $this->syncRunRepository->findByUuidOrFail($uuid)->load([
            'config:id,uuid,name,slug,direction,default_mode',
            'integration:id,uuid,name,slug',
            'actor:id,uuid,full_name,email',
        ]);

        return [
            'config' => $run->config,
            'run' => $run,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listRuns(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['sync_config']) && empty($filters['sync_config_id'])) {
            $config = $this->syncConfigRepository->findByIdentifierOrFail((string) $filters['sync_config']);
            $filters['sync_config_id'] = $config->id;
        }
        $this->normalizeCompanyFilter($filters);

        return $this->syncRunRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listLogs(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['sync_run']) && empty($filters['sync_run_id'])) {
            $run = $this->syncRunRepository->findByUuidOrFail((string) $filters['sync_run']);
            $filters['sync_run_id'] = $run->id;
        }
        if (! empty($filters['sync_config']) && empty($filters['sync_config_id'])) {
            $config = $this->syncConfigRepository->findByIdentifierOrFail((string) $filters['sync_config']);
            $filters['sync_config_id'] = $config->id;
        }

        return $this->syncLogRepository->paginateFiltered($filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $companyIdentifier = null): array
    {
        $companyId = null;
        if ($companyIdentifier) {
            $companyId = $this->companyRepository->findByIdentifierOrFail($companyIdentifier)->id;
        }

        $stats = $this->syncRunRepository->dashboardStats($companyId);
        $configs = $this->syncConfigRepository->paginateFiltered([
            'company_id' => $companyId,
            'per_page' => 5,
        ]);

        return [
            'totals' => $stats['totals'],
            'recent_runs' => $stats['recent_runs'],
            'configs' => $configs,
        ];
    }

    public function dispatchDueSchedules(): int
    {
        $due = 0;
        foreach ($this->syncConfigRepository->dueScheduled() as $config) {
            if (! $config->schedule_cron || ! $this->scheduler->isDue($config->schedule_cron)) {
                continue;
            }

            $run = $this->syncRunRepository->createRun([
                'sync_config_id' => $config->id,
                'company_id' => $config->company_id,
                'integration_id' => $config->integration_id,
                'trigger' => SyncTrigger::Scheduled->value,
                'mode' => $config->default_mode?->value ?? 'incremental',
                'direction' => $config->direction?->value ?? 'import',
                'status' => SyncRunStatus::Queued->value,
            ]);

            event(new SyncRunStarted($config, $run, null));
            $this->dispatchBackgroundSync($run);
            $due++;
        }

        return $due;
    }

    protected function dispatchBackgroundSync(SyncRun $run): void
    {
        $direction = $run->direction?->value ?? $run->direction ?? 'import';

        match ($direction) {
            'export' => ProcessExportJob::dispatch($run->id, $run->company_id, $run->triggered_by),
            'import' => ProcessImportJob::dispatch($run->id, $run->company_id, $run->triggered_by),
            default => RunIntegrationSyncJob::dispatch($run->id, $run->company_id, $run->triggered_by),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function writeLog(
        SyncRun $run,
        int $configId,
        string $level,
        ?string $action,
        ?string $recordKey,
        string $message,
        array $context = [],
    ): void {
        if ($message === '') {
            return;
        }

        $this->syncLogRepository->createLog([
            'sync_run_id' => $run->id,
            'sync_config_id' => $configId,
            'level' => $level,
            'action' => $action,
            'record_key' => $recordKey,
            'message' => Str::limit($message, 2000),
            'context' => $context,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareConfigPayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'name', 'slug', 'description', 'direction', 'default_mode', 'trigger_type',
            'schedule_cron', 'is_enabled', 'source_path', 'target_path', 'entity_type',
            'conflict_strategy', 'batch_size', 'cursor_field', 'cursor_value',
            'field_mapping', 'filters', 'options',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));
        foreach (['description', 'schedule_cron', 'source_path', 'target_path', 'cursor_field', 'cursor_value', 'slug'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        return $payload;
    }

    protected function uniqueSlug(int $companyId, ?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'sync';
        $candidate = $base;
        $i = 2;
        while ($this->syncConfigRepository->slugExists($companyId, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function normalizeCompanyFilter(array &$filters): void
    {
        $companyIdentifier = $filters['company'] ?? $filters['company_id'] ?? null;
        if (! empty($companyIdentifier) && ! is_numeric($companyIdentifier)) {
            $filters['company_id'] = $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier)->id;
        }
    }
}
