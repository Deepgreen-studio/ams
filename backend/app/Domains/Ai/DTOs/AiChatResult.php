<?php

namespace App\Domains\Ai\DTOs;

final class AiChatResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly int $tokensIn = 0,
        public readonly int $tokensOut = 0,
        public readonly ?string $finishReason = null,
        public readonly ?string $requestId = null,
        public readonly int $latencyMs = 0,
        public readonly array $raw = [],
    ) {}
}
