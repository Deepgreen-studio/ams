<?php

namespace App\Domains\Automation\Services;

use App\Domains\Automation\Enums\AutomationConditionOperator;
use App\Domains\Automation\Models\AutomationCondition;
use Illuminate\Support\Collection;

class AutomationConditionEvaluator
{
    /**
     * @param  Collection<int, AutomationCondition>  $conditions
     * @param  array<string, mixed>  $context
     */
    public function passes(Collection $conditions, array $context, string $logic = 'and'): bool
    {
        if ($conditions->isEmpty()) {
            return true;
        }

        $results = $conditions->map(fn (AutomationCondition $condition) => $this->evaluate($condition, $context));

        return strtolower($logic) === 'or'
            ? $results->contains(true)
            : $results->every(fn (bool $result) => $result);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function evaluate(AutomationCondition $condition, array $context): bool
    {
        $field = strtolower((string) $condition->field);
        $actual = $this->resolveValue($context, $field);
        $expected = $condition->value;
        $operator = $condition->operator instanceof AutomationConditionOperator
            ? $condition->operator
            : AutomationConditionOperator::tryFrom((string) $condition->operator);

        if ($operator === null) {
            return false;
        }

        return match ($operator) {
            AutomationConditionOperator::Equals => $this->normalize($actual) === $this->normalize($expected),
            AutomationConditionOperator::NotEquals => $this->normalize($actual) !== $this->normalize($expected),
            AutomationConditionOperator::Contains => str_contains((string) $actual, (string) $expected),
            AutomationConditionOperator::In => in_array($this->normalize($actual), $this->splitList($expected), true),
            AutomationConditionOperator::NotIn => ! in_array($this->normalize($actual), $this->splitList($expected), true),
            AutomationConditionOperator::GreaterThan => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            AutomationConditionOperator::LessThan => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            AutomationConditionOperator::IsSet => ! blank($actual),
            AutomationConditionOperator::IsEmpty => blank($actual),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveValue(array $context, string $field): mixed
    {
        if (array_key_exists($field, $context)) {
            return $context[$field];
        }

        // Support nested keys like ticket.priority via dot notation.
        $segments = explode('.', $field);
        $value = $context;
        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
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
    private function splitList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $item) => $this->normalize($item),
            preg_split('/\s*,\s*/', $value) ?: []
        )));
    }
}
