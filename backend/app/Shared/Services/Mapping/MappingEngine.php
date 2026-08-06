<?php

namespace App\Shared\Services\Mapping;

use App\Shared\Services\Mapping\DTOs\FieldMappingRuleDto;
use App\Shared\Services\Mapping\DTOs\MappingResultDto;

/**
 * Enterprise Data Mapping Engine.
 *
 * Future modules MUST transform external↔internal payloads through this engine
 * instead of ad-hoc field mapping.
 */
class MappingEngine
{
    public function __construct(
        private readonly FieldMapper $fieldMapper,
        private readonly MappingValidator $validator,
        private readonly DataTransformer $transformer,
        private readonly RuleEngine $ruleEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $source
     * @param  list<FieldMappingRuleDto|array<string, mixed>>  $rules
     */
    public function map(array $source, array $rules, string $direction = 'inbound'): MappingResultDto
    {
        $normalized = $this->normalizeRules($rules);
        $mapped = $this->fieldMapper->map($source, $normalized, $direction);
        $validationErrors = $this->validator->validate($normalized, $mapped->output, $source);
        $errors = array_values(array_unique(array_merge($mapped->errors, $validationErrors)));

        return new MappingResultDto(
            output: $mapped->output,
            valid: $errors === [],
            errors: $errors,
            warnings: $mapped->warnings,
            applied: $mapped->applied,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<FieldMappingRuleDto|array<string, mixed>>  $rules
     * @return list<MappingResultDto>
     */
    public function mapMany(array $records, array $rules, string $direction = 'inbound'): array
    {
        return array_map(
            fn (array $record) => $this->map($record, $rules, $direction),
            $records
        );
    }

    /**
     * @param  list<FieldMappingRuleDto|array<string, mixed>>  $rules
     * @return list<string>
     */
    public function validateDefinition(array $rules): array
    {
        return $this->validator->validateDefinition($this->normalizeRules($rules));
    }

    public function transformer(): DataTransformer
    {
        return $this->transformer;
    }

    public function rules(): RuleEngine
    {
        return $this->ruleEngine;
    }

    /**
     * Catalog of supported transform types for UI field selectors.
     *
     * @return list<array{value: string, label: string, config_keys: list<string>}>
     */
    public function transformCatalog(): array
    {
        return [
            ['value' => 'none', 'label' => 'None', 'config_keys' => []],
            ['value' => 'trim', 'label' => 'Trim', 'config_keys' => []],
            ['value' => 'uppercase', 'label' => 'Uppercase', 'config_keys' => []],
            ['value' => 'lowercase', 'label' => 'Lowercase', 'config_keys' => []],
            ['value' => 'title_case', 'label' => 'Title Case', 'config_keys' => []],
            ['value' => 'cast_string', 'label' => 'Cast String', 'config_keys' => []],
            ['value' => 'cast_int', 'label' => 'Cast Integer', 'config_keys' => []],
            ['value' => 'cast_float', 'label' => 'Cast Float', 'config_keys' => []],
            ['value' => 'cast_bool', 'label' => 'Cast Boolean', 'config_keys' => []],
            ['value' => 'date_format', 'label' => 'Date Format', 'config_keys' => ['format']],
            ['value' => 'replace', 'label' => 'Replace', 'config_keys' => ['search', 'replace']],
            ['value' => 'prefix', 'label' => 'Prefix', 'config_keys' => ['value']],
            ['value' => 'suffix', 'label' => 'Suffix', 'config_keys' => ['value']],
            ['value' => 'split_first', 'label' => 'Split First', 'config_keys' => ['delimiter']],
            ['value' => 'split_last', 'label' => 'Split Last', 'config_keys' => ['delimiter']],
            ['value' => 'lookup', 'label' => 'Lookup Map', 'config_keys' => ['map']],
            ['value' => 'template', 'label' => 'Template', 'config_keys' => ['template']],
        ];
    }

    /**
     * Built-in AMS internal entity / field catalog for the Field Selector.
     *
     * @return list<array{entity: string, label: string, fields: list<array{path: string, label: string, type: string}>}>
     */
    public function internalFieldCatalog(): array
    {
        return [
            [
                'entity' => 'Users',
                'label' => 'Users',
                'fields' => [
                    ['path' => 'Users.first_name', 'label' => 'First Name', 'type' => 'string'],
                    ['path' => 'Users.last_name', 'label' => 'Last Name', 'type' => 'string'],
                    ['path' => 'Users.email', 'label' => 'Email', 'type' => 'string'],
                    ['path' => 'Users.phone', 'label' => 'Phone', 'type' => 'string'],
                    ['path' => 'Users.status', 'label' => 'Status', 'type' => 'string'],
                ],
            ],
            [
                'entity' => 'Health',
                'label' => 'Health',
                'fields' => [
                    ['path' => 'Health.weight', 'label' => 'Weight', 'type' => 'float'],
                    ['path' => 'Health.height', 'label' => 'Height', 'type' => 'float'],
                    ['path' => 'Health.bmi', 'label' => 'BMI', 'type' => 'float'],
                    ['path' => 'Health.notes', 'label' => 'Notes', 'type' => 'string'],
                ],
            ],
            [
                'entity' => 'Companies',
                'label' => 'Companies',
                'fields' => [
                    ['path' => 'Companies.company_name', 'label' => 'Company Name', 'type' => 'string'],
                    ['path' => 'Companies.email', 'label' => 'Email', 'type' => 'string'],
                    ['path' => 'Companies.phone', 'label' => 'Phone', 'type' => 'string'],
                    ['path' => 'Companies.status', 'label' => 'Status', 'type' => 'string'],
                ],
            ],
            [
                'entity' => 'Customers',
                'label' => 'Customers',
                'fields' => [
                    ['path' => 'Customers.name', 'label' => 'Name', 'type' => 'string'],
                    ['path' => 'Customers.email', 'label' => 'Email', 'type' => 'string'],
                    ['path' => 'Customers.external_id', 'label' => 'External ID', 'type' => 'string'],
                ],
            ],
        ];
    }

    /**
     * @param  list<FieldMappingRuleDto|array<string, mixed>>  $rules
     * @return list<FieldMappingRuleDto>
     */
    protected function normalizeRules(array $rules): array
    {
        return array_values(array_map(
            fn ($rule) => $rule instanceof FieldMappingRuleDto ? $rule : FieldMappingRuleDto::fromArray((array) $rule),
            $rules
        ));
    }
}
