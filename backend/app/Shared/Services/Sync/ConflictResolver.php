<?php

namespace App\Shared\Services\Sync;

use App\Shared\Services\Sync\DTOs\SyncRecordDto;

class ConflictResolver
{
    /**
     * @param  array<string, string>  $snapshot key => hash
     * @return array{action: string, snapshot: array<string, string>}
     */
    public function resolve(SyncRecordDto $record, array $snapshot, string $strategy = 'skip'): array
    {
        $key = $record->key;
        $hash = $record->hash();
        $exists = array_key_exists($key, $snapshot);
        $unchanged = $exists && hash_equals($snapshot[$key], $hash);

        if (! $exists) {
            $snapshot[$key] = $hash;

            return ['action' => 'import', 'snapshot' => $snapshot];
        }

        if ($unchanged) {
            return ['action' => 'skip', 'snapshot' => $snapshot];
        }

        return match ($strategy) {
            'overwrite', 'merge' => $this->applyUpdate($snapshot, $key, $hash, $strategy === 'merge' ? 'merge' : 'update'),
            'manual' => ['action' => 'skip', 'snapshot' => $snapshot],
            default => ['action' => 'skip', 'snapshot' => $snapshot],
        };
    }

    /**
     * @param  array<string, string>  $snapshot
     * @return array{action: string, snapshot: array<string, string>}
     */
    protected function applyUpdate(array $snapshot, string $key, string $hash, string $action): array
    {
        $snapshot[$key] = $hash;

        return ['action' => $action, 'snapshot' => $snapshot];
    }
}
