<?php

namespace App\Domains\Ai\DTOs;

final class AiCompletionRequest
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly string $prompt,
        public readonly ?string $model = null,
        public readonly ?float $temperature = null,
        public readonly ?int $maxTokens = null,
        public readonly array $options = [],
    ) {}
}
