<?php

namespace App\Shared\Services\Mapping\DTOs;

class MappingResultDto
{
    /**
     * @param  array<string, mixed>  $output
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @param  array<string, mixed>  $applied
     */
    public function __construct(
        public readonly array $output,
        public readonly bool $valid,
        public readonly array $errors = [],
        public readonly array $warnings = [],
        public readonly array $applied = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'output' => $this->output,
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'applied' => $this->applied,
        ];
    }
}
