<?php

namespace App\Domains\Workflows\Services;

class WorkflowConditionEvaluator
{
    /**
     * Evaluate step condition config against context.
     *
     * Config shape:
     * {
     *   "logic": "and|or",
     *   "rules": [{"field":"priority","operator":"equals","value":"high"}]
     * }
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     */
    public function passes(array $config, array $context): bool
    {
        $rules = $config['rules'] ?? $config['conditions'] ?? [];
        if (! is_array($rules) || $rules === []) {
            return true;
        }

        $logic = strtolower((string) ($config['logic'] ?? 'and'));
        $results = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                $results[] = false;

                continue;
            }
            $results[] = $this->evaluateRule($rule, $context);
        }

        return $logic === 'or'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $context
     */
    private function evaluateRule(array $rule, array $context): bool
    {
        $field = strtolower((string) ($rule['field'] ?? ''));
        $operator = strtolower((string) ($rule['operator'] ?? 'equals'));
        $expected = $rule['value'] ?? null;
        $actual = $this->resolveValue($context, $field);

        return match ($operator) {
            'equals', 'eq' => $this->normalize($actual) === $this->normalize($expected),
            'not_equals', 'neq' => $this->normalize($actual) !== $this->normalize($expected),
            'contains' => str_contains((string) $actual, (string) $expected),
            'in' => in_array($this->normalize($actual), $this->splitList($expected), true),
            'not_in' => ! in_array($this->normalize($actual), $this->splitList($expected), true),
            'greater_than', 'gt' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'less_than', 'lt' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            'is_set' => ! blank($actual),
            'is_empty' => blank($actual),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveValue(array $context, string $field): mixed
    {
        if ($field === '') {
            return null;
        }

        if (array_key_exists($field, $context)) {
            return $context[$field];
        }

        return data_get($context, $field);
    }

    private function normalize(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return strtolower(trim((string) $value));
    }

    /**
     * @return list<string>
     */
    private function splitList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map(fn ($item) => $this->normalize($item), $value));
        }

        if ($value === null || trim((string) $value) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $item) => $this->normalize($item),
            preg_split('/\s*,\s*/', (string) $value) ?: []
        )));
    }
}
