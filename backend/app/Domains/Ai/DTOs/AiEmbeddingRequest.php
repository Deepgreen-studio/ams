<?php

namespace App\Domains\Ai\DTOs;

final class AiEmbeddingRequest
{
    /**
     * @param  list<string>  $inputs
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly array $inputs,
        public readonly ?string $model = null,
        public readonly array $options = [],
    ) {}
}
