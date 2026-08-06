<?php

namespace App\Shared\Services\Mapping;

use App\Shared\Services\Mapping\DTOs\FieldMappingRuleDto;
use App\Shared\Services\Mapping\DTOs\MappingResultDto;

class MappingValidator
{
    /**
     * @param  list<FieldMappingRuleDto>  $rules
     * @param  array<string, mixed>  $output
     * @param  array<string, mixed>  $source
     * @return list<string>
     */
    public function validate(array $rules, array $output, array $source): array
    {
        $errors = [];

        foreach ($rules as $rule) {
            if (! $rule->isEnabled || ! $rule->isRequired) {
                continue;
            }

            $value = $this->getNested($output, $rule->internalField);
            if ($this->isEmpty($value)) {
                $errors[] = "Required field [{$rule->internalField}] is missing (mapped from [{$rule->externalField}]).";
            }
        }

        return $errors;
    }

    /**
     * @param  list<FieldMappingRuleDto>  $rules
     * @return list<string>
     */
    public function validateDefinition(array $rules): array
    {
        $errors = [];
        $pairs = [];

        if ($rules === []) {
            $errors[] = 'At least one field mapping is required.';
        }

        foreach ($rules as $index => $rule) {
            $n = $index + 1;
            if (blank($rule->externalField)) {
                $errors[] = "Field #{$n}: external_field is required.";
            }
            if (blank($rule->internalField)) {
                $errors[] = "Field #{$n}: internal_field is required.";
            }

            $pair = $rule->externalField.'=>'.$rule->internalField;
            if (isset($pairs[$pair])) {
                $errors[] = "Duplicate mapping pair: {$pair}.";
            }
            $pairs[$pair] = true;
        }

        return $errors;
    }

    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getNested(array $data, string $path): mixed
    {
        if (! str_contains($path, '.')) {
            return $data[$path] ?? null;
        }

        $segments = explode('.', $path);
        $current = $data;
        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }
}
