<?php

namespace App\Shared\Services\Sync;

use App\Shared\Services\Http\ApiClientService;
use App\Shared\Services\Sync\DTOs\SyncRecordDto;

class ImportManager
{
    public function __construct(
        private readonly ApiClientService $apiClientService,
    ) {}

    /**
     * Fetch records for import.
     *
     * @param  array<string, mixed>  $connection  Integration connection context for ApiClientService
     * @param  array<string, mixed>  $config
     * @return list<SyncRecordDto>
     */
    public function fetch(array $connection, array $config): array
    {
        $mode = (string) ($config['mode'] ?? 'full');
        $sourcePath = (string) ($config['source_path'] ?? '/');
        $query = (array) ($config['filters'] ?? []);

        if ($mode === 'incremental' && ! empty($config['cursor_field']) && ! empty($config['cursor_value'])) {
            $query[(string) $config['cursor_field']] = $config['cursor_value'];
        }

        if (! empty($config['sample_records']) && is_array($config['sample_records'])) {
            return $this->normalizeRecords($config['sample_records'], $config);
        }

        $response = $this->apiClientService->sendFromConnection($connection, [
            'method' => 'GET',
            'path' => $sourcePath,
            'query' => $query,
            'apply_auth' => true,
            'timeout' => $config['timeout'] ?? null,
            'retry_attempts' => $config['retry_attempts'] ?? null,
        ]);

        if (! $response->successful) {
            throw new \RuntimeException($response->error ?: 'Import fetch failed with HTTP '.$response->statusCode);
        }

        $payload = is_array($response->body) ? $response->body : [];
        $rows = $this->extractRows($payload);

        return $this->normalizeRecords($rows, $config);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    protected function extractRows(array $payload): array
    {
        if (array_is_list($payload)) {
            return $payload;
        }

        foreach (['data', 'items', 'records', 'results'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return array_values($payload[$key]);
            }
        }

        return [$payload];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $config
     * @return list<SyncRecordDto>
     */
    protected function normalizeRecords(array $rows, array $config): array
    {
        $keyField = (string) ($config['key_field'] ?? 'id');
        $mapping = (array) ($config['field_mapping'] ?? []);
        $records = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $mapped = $mapping === [] ? $row : $this->mapFields($row, $mapping);
            $key = (string) ($mapped[$keyField] ?? $row[$keyField] ?? ('row-'.$index));
            $records[] = new SyncRecordDto($key, $mapped, ['source_index' => $index]);
        }

        return $records;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $mapping source => target
     * @return array<string, mixed>
     */
    protected function mapFields(array $row, array $mapping): array
    {
        $mapped = [];
        foreach ($mapping as $source => $target) {
            if (array_key_exists($source, $row)) {
                $mapped[$target] = $row[$source];
            }
        }

        return $mapped === [] ? $row : $mapped;
    }
}
