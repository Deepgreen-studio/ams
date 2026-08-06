<?php

namespace App\Shared\Services\Sync;

use App\Shared\Services\Sync\DTOs\SyncRecordDto;
use App\Shared\Services\Sync\DTOs\SyncResultDto;

/**
 * Enterprise API Synchronization Engine.
 *
 * Future modules MUST use this service for sync orchestration primitives.
 * Domain services own persistence of sync configs/runs/logs.
 */
class SyncService
{
    public function __construct(
        private readonly ImportManager $importManager,
        private readonly ExportManager $exportManager,
        private readonly Scheduler $scheduler,
        private readonly ConflictResolver $conflictResolver,
    ) {}

    public function imports(): ImportManager
    {
        return $this->importManager;
    }

    public function exports(): ExportManager
    {
        return $this->exportManager;
    }

    public function scheduler(): Scheduler
    {
        return $this->scheduler;
    }

    public function conflicts(): ConflictResolver
    {
        return $this->conflictResolver;
    }

    /**
     * Execute a sync operation.
     *
     * @param  array<string, mixed>  $connection
     * @param  array<string, mixed>  $config
     */
    public function execute(array $connection, array $config): SyncResultDto
    {
        $direction = (string) ($config['direction'] ?? 'import');
        $mode = (string) ($config['mode'] ?? 'full');
        $strategy = (string) ($config['conflict_strategy'] ?? 'skip');
        $snapshot = (array) ($config['record_snapshot'] ?? []);
        $logs = [];

        try {
            if ($direction === 'export') {
                return $this->executeExport($connection, $config, $logs);
            }

            if ($direction === 'bidirectional') {
                $importResult = $this->executeImport($connection, $config, $snapshot, $strategy, $mode, $logs);
                $exportConfig = array_merge($config, [
                    'sample_export' => (bool) ($config['sample_export'] ?? $config['sample_records'] ?? false),
                ]);
                $exportRecords = $this->snapshotToRecords($importResult->snapshot, $config);
                $exportPush = $this->exportManager->push($connection, $exportConfig, $exportRecords);
                $logs = array_merge($importResult->logs, $exportPush['logs']);

                return new SyncResultDto(
                    successful: $importResult->successful && $exportPush['failed'] === 0,
                    totalRecords: $importResult->totalRecords + count($exportRecords),
                    imported: $importResult->imported,
                    exported: $exportPush['exported'],
                    updated: $importResult->updated,
                    failed: $importResult->failed + $exportPush['failed'],
                    skipped: $importResult->skipped,
                    logs: $logs,
                    snapshot: $importResult->snapshot,
                    cursorValue: $importResult->cursorValue,
                    error: $importResult->error,
                    meta: ['direction' => 'bidirectional', 'mode' => $mode],
                );
            }

            return $this->executeImport($connection, $config, $snapshot, $strategy, $mode, $logs);
        } catch (\Throwable $exception) {
            $logs[] = [
                'level' => 'error',
                'action' => 'sync',
                'message' => $exception->getMessage(),
                'context' => [],
            ];

            return new SyncResultDto(
                successful: false,
                logs: $logs,
                snapshot: $snapshot,
                error: $exception->getMessage(),
                meta: ['direction' => $direction, 'mode' => $mode],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $connection
     * @param  array<string, mixed>  $config
     * @param  array<string, string>  $snapshot
     * @param  list<array<string, mixed>>  $logs
     */
    protected function executeImport(
        array $connection,
        array $config,
        array $snapshot,
        string $strategy,
        string $mode,
        array &$logs,
    ): SyncResultDto {
        $records = $this->importManager->fetch($connection, $config);
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $cursorValue = $config['cursor_value'] ?? null;
        $cursorField = $config['cursor_field'] ?? null;

        $logs[] = [
            'level' => 'info',
            'action' => 'import',
            'message' => 'Fetched '.count($records).' records for '.$mode.' import.',
            'context' => ['count' => count($records)],
        ];

        foreach ($records as $record) {
            try {
                $resolved = $this->conflictResolver->resolve($record, $snapshot, $strategy);
                $snapshot = $resolved['snapshot'];

                match ($resolved['action']) {
                    'import' => $imported++,
                    'update', 'merge' => $updated++,
                    default => $skipped++,
                };

                $logs[] = [
                    'level' => $resolved['action'] === 'skip' ? 'info' : 'success',
                    'action' => $resolved['action'],
                    'record_key' => $record->key,
                    'message' => 'Record '.$record->key.' → '.$resolved['action'],
                    'context' => [],
                ];

                if ($cursorField && array_key_exists($cursorField, $record->data)) {
                    $cursorValue = (string) $record->data[$cursorField];
                }
            } catch (\Throwable $exception) {
                $failed++;
                $logs[] = [
                    'level' => 'error',
                    'action' => 'fail',
                    'record_key' => $record->key,
                    'message' => $exception->getMessage(),
                    'context' => [],
                ];
            }
        }

        return new SyncResultDto(
            successful: $failed === 0,
            totalRecords: count($records),
            imported: $imported,
            updated: $updated,
            failed: $failed,
            skipped: $skipped,
            logs: $logs,
            snapshot: $snapshot,
            cursorValue: is_string($cursorValue) ? $cursorValue : null,
            meta: ['direction' => 'import', 'mode' => $mode],
        );
    }

    /**
     * @param  array<string, mixed>  $connection
     * @param  array<string, mixed>  $config
     * @param  list<array<string, mixed>>  $logs
     */
    protected function executeExport(array $connection, array $config, array &$logs): SyncResultDto
    {
        $records = [];
        if (! empty($config['sample_records']) && is_array($config['sample_records'])) {
            foreach ($config['sample_records'] as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $key = (string) ($row['id'] ?? $row['key'] ?? 'export-'.$index);
                $records[] = new SyncRecordDto($key, $row);
            }
        } else {
            $records = $this->snapshotToRecords((array) ($config['record_snapshot'] ?? []), $config);
        }

        $result = $this->exportManager->push($connection, $config, $records);
        $logs = array_merge($logs, $result['logs']);

        return new SyncResultDto(
            successful: $result['failed'] === 0,
            totalRecords: count($records),
            exported: $result['exported'],
            failed: $result['failed'],
            logs: $logs,
            snapshot: (array) ($config['record_snapshot'] ?? []),
            meta: ['direction' => 'export', 'mode' => $config['mode'] ?? 'full'],
        );
    }

    /**
     * @param  array<string, string>  $snapshot
     * @param  array<string, mixed>  $config
     * @return list<SyncRecordDto>
     */
    protected function snapshotToRecords(array $snapshot, array $config): array
    {
        $records = [];
        foreach ($snapshot as $key => $hash) {
            $records[] = new SyncRecordDto((string) $key, [
                'id' => $key,
                'hash' => $hash,
                'entity_type' => $config['entity_type'] ?? 'generic',
            ]);
        }

        return $records;
    }
}
