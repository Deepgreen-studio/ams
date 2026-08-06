<?php

namespace App\Domains\Ai\DTOs;

final class AiChatRequest
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly array $messages,
        public readonly ?string $model = null,
        public readonly ?float $temperature = null,
        public readonly ?int $maxTokens = null,
        public readonly array $options = [],
    ) {}
}
