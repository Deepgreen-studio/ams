<?php

namespace App\Shared\Services\Sync\DTOs;

final class SyncRecordDto
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $key,
        public readonly array $data,
        public readonly array $meta = [],
    ) {}

    public function hash(): string
    {
        return hash('sha256', json_encode($this->data, JSON_THROW_ON_ERROR));
    }
}
