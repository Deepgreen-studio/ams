<?php

namespace App\Domains\Ai\DTOs;

final class AiCategorizeResult
{
    /**
     * @param  list<array{label: string, confidence: float}>  $predictions
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly array $predictions,
        public readonly string $model,
        public readonly int $tokensIn = 0,
        public readonly int $tokensOut = 0,
        public readonly int $latencyMs = 0,
        public readonly array $raw = [],
    ) {}
}
