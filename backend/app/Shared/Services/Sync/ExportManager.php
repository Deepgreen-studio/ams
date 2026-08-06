<?php

namespace App\Shared\Services\Sync;

use App\Shared\Services\Http\ApiClientService;
use App\Shared\Services\Sync\DTOs\SyncRecordDto;

class ExportManager
{
    public function __construct(
        private readonly ApiClientService $apiClientService,
    ) {}

    /**
     * Export records to a remote endpoint in batches.
     *
     * @param  array<string, mixed>  $connection
     * @param  array<string, mixed>  $config
     * @param  list<SyncRecordDto>  $records
     * @return array{exported: int, failed: int, logs: list<array<string, mixed>>}
     */
    public function push(array $connection, array $config, array $records): array
    {
        $targetPath = (string) ($config['target_path'] ?? '/');
        $batchSize = max(1, min((int) ($config['batch_size'] ?? 100), 500));
        $exported = 0;
        $failed = 0;
        $logs = [];

        foreach (array_chunk($records, $batchSize) as $batchIndex => $batch) {
            $payload = [
                'items' => array_map(static fn (SyncRecordDto $record): array => [
                    'key' => $record->key,
                    'data' => $record->data,
                ], $batch),
            ];

            if (! empty($config['sample_export'])) {
                $exported += count($batch);
                $logs[] = [
                    'level' => 'success',
                    'action' => 'export',
                    'message' => 'Sample export batch '.($batchIndex + 1).' accepted locally.',
                    'context' => ['batch' => $batchIndex + 1, 'count' => count($batch)],
                ];
                continue;
            }

            $response = $this->apiClientService->sendFromConnection($connection, [
                'method' => (string) ($config['export_method'] ?? 'POST'),
                'path' => $targetPath,
                'body' => $payload,
                'apply_auth' => true,
                'timeout' => $config['timeout'] ?? null,
                'retry_attempts' => $config['retry_attempts'] ?? null,
            ]);

            if ($response->successful) {
                $exported += count($batch);
                $logs[] = [
                    'level' => 'success',
                    'action' => 'export',
                    'message' => 'Exported batch '.($batchIndex + 1).' ('.count($batch).' records).',
                    'context' => ['status' => $response->statusCode],
                ];
            } else {
                $failed += count($batch);
                $logs[] = [
                    'level' => 'error',
                    'action' => 'export',
                    'message' => 'Export batch '.($batchIndex + 1).' failed: '.($response->error ?: 'HTTP '.$response->statusCode),
                    'context' => ['status' => $response->statusCode],
                ];
            }
        }

        return compact('exported', 'failed', 'logs');
    }
}
