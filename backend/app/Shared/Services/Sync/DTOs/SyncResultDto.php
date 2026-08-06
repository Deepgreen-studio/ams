<?php

namespace App\Shared\Services\Sync\DTOs;

final class SyncResultDto
{
    /**
     * @param  list<array<string, mixed>>  $logs
     * @param  array<string, string>  $snapshot
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly bool $successful,
        public int $totalRecords = 0,
        public int $imported = 0,
        public int $exported = 0,
        public int $updated = 0,
        public int $failed = 0,
        public int $skipped = 0,
        public readonly array $logs = [],
        public readonly array $snapshot = [],
        public readonly ?string $cursorValue = null,
        public readonly ?string $error = null,
        public readonly array $meta = [],
    ) {}

    public function progressPercent(): int
    {
        if ($this->totalRecords <= 0) {
            return $this->successful ? 100 : 0;
        }

        $processed = min($this->totalRecords, $this->imported + $this->exported + $this->updated + $this->failed + $this->skipped);

        return (int) min(100, round(($processed / $this->totalRecords) * 100));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'total_records' => $this->totalRecords,
            'imported' => $this->imported,
            'exported' => $this->exported,
            'updated' => $this->updated,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'progress_percent' => $this->progressPercent(),
            'cursor_value' => $this->cursorValue,
            'error' => $this->error,
            'meta' => $this->meta,
            'logs' => $this->logs,
        ];
    }
}
