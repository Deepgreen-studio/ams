<?php

namespace App\Domains\Ai\DTOs;

final class AiTextRequest
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly string $text,
        public readonly ?string $model = null,
        public readonly array $options = [],
    ) {}
}
