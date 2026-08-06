<?php

namespace App\Domains\Ai\DTOs;

final class AiEmbeddingResult
{
    /**
     * @param  list<list<float>>  $embeddings
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly array $embeddings,
        public readonly string $model,
        public readonly int $tokensIn = 0,
        public readonly int $latencyMs = 0,
        public readonly array $raw = [],
    ) {}
}
