<?php

namespace App\Domains\Ai\DTOs;

final class AiCategorizeRequest
{
    /**
     * @param  list<string>  $labels
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly string $text,
        public readonly array $labels = [],
        public readonly ?string $model = null,
        public readonly array $options = [],
    ) {}
}
