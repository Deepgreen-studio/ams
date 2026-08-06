<?php

namespace App\Shared\Services\Mapping;

use App\Shared\Services\Mapping\DTOs\FieldMappingRuleDto;
use App\Shared\Services\Mapping\DTOs\MappingResultDto;

class FieldMapper
{
    public function __construct(
        private readonly DataTransformer $transformer,
        private readonly RuleEngine $ruleEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $source
     * @param  list<FieldMappingRuleDto>  $rules
     */
    public function map(array $source, array $rules, string $direction = 'inbound'): MappingResultDto
    {
        $sorted = $rules;
        usort($sorted, fn (FieldMappingRuleDto $a, FieldMappingRuleDto $b) => $a->sortOrder <=> $b->sortOrder);

        $output = [];
        $errors = [];
        $warnings = [];
        $applied = [];

        foreach ($sorted as $rule) {
            if (! $rule->isEnabled) {
                continue;
            }

            $from = $direction === 'outbound' ? $rule->internalField : $rule->externalField;
            $to = $direction === 'outbound' ? $rule->externalField : $rule->internalField;

            $raw = $this->getNested($source, $from);
            if ($this->isEmpty($raw) && $rule->defaultValue !== null && $rule->defaultValue !== '') {
                $raw = $this->castDefault($rule->defaultValue);
                $warnings[] = "Default applied for [{$to}] from [{$from}].";
            }

            $transformed = $this->transformer->transform($raw, $rule);
            $ruleErrors = $this->ruleEngine->evaluate($transformed, $rule->customRules, $to);
            $errors = array_merge($errors, $ruleErrors);

            if (! $this->isEmpty($transformed) || $rule->isRequired || ($rule->defaultValue !== null && $rule->defaultValue !== '')) {
                $this->setNested($output, $to, $transformed);
                $applied[] = [
                    'from' => $from,
                    'to' => $to,
                    'transform' => $rule->transformType,
                    'value' => $transformed,
                ];
            }
        }

        return new MappingResultDto(
            output: $output,
            valid: $errors === [],
            errors: $errors,
            warnings: $warnings,
            applied: $applied,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function getNested(array $data, string $path): mixed
    {
        if ($path === '') {
            return null;
        }

        if (! str_contains($path, '.')) {
            return $data[$path] ?? null;
        }

        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function setNested(array &$data, string $path, mixed $value): void
    {
        if (! str_contains($path, '.')) {
            $data[$path] = $value;

            return;
        }

        $segments = explode('.', $path);
        $ref = &$data;
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;

                return;
            }
            if (! isset($ref[$segment]) || ! is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }

    protected function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    protected function castDefault(mixed $default): mixed
    {
        if (! is_string($default)) {
            return $default;
        }

        $trimmed = trim($default);
        if ($trimmed === 'true') {
            return true;
        }
        if ($trimmed === 'false') {
            return false;
        }
        if ($trimmed === 'null') {
            return null;
        }
        if (is_numeric($trimmed)) {
            return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
        }

        $json = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return $default;
    }
}
