<?php

namespace App\Domains\Ai\DTOs;

final class AiHealthResult
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly bool $healthy,
        public readonly string $message,
        public readonly array $details = [],
        public readonly int $latencyMs = 0,
    ) {}
}
