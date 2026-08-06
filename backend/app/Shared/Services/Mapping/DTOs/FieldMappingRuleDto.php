<?php

namespace App\Shared\Services\Mapping\DTOs;

class FieldMappingRuleDto
{
    /**
     * @param  array<string, mixed>  $transformConfig
     * @param  list<array<string, mixed>>  $customRules
     */
    public function __construct(
        public readonly string $externalField,
        public readonly string $internalField,
        public readonly string $transformType = 'none',
        public readonly array $transformConfig = [],
        public readonly bool $isRequired = false,
        public readonly mixed $defaultValue = null,
        public readonly array $customRules = [],
        public readonly int $sortOrder = 0,
        public readonly bool $isEnabled = true,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            externalField: (string) ($data['external_field'] ?? ''),
            internalField: (string) ($data['internal_field'] ?? ''),
            transformType: (string) ($data['transform_type'] ?? 'none'),
            transformConfig: (array) ($data['transform_config'] ?? []),
            isRequired: (bool) ($data['is_required'] ?? false),
            defaultValue: $data['default_value'] ?? null,
            customRules: array_values((array) ($data['custom_rules'] ?? [])),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isEnabled: (bool) ($data['is_enabled'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'external_field' => $this->externalField,
            'internal_field' => $this->internalField,
            'transform_type' => $this->transformType,
            'transform_config' => $this->transformConfig,
            'is_required' => $this->isRequired,
            'default_value' => $this->defaultValue,
            'custom_rules' => $this->customRules,
            'sort_order' => $this->sortOrder,
            'is_enabled' => $this->isEnabled,
        ];
    }
}
