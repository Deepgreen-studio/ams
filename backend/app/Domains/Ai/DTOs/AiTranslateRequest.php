<?php

namespace App\Domains\Ai\DTOs;

final class AiTranslateRequest
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly string $text,
        public readonly string $targetLocale,
        public readonly ?string $sourceLocale = null,
        public readonly ?string $model = null,
        public readonly array $options = [],
    ) {}
}
