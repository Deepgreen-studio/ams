<?php

namespace App\Shared\Services\Mapping;

use App\Shared\Services\Mapping\DTOs\FieldMappingRuleDto;

class RuleEngine
{
    /**
     * Evaluate custom rules against a source/value pair.
     *
     * Supported rules:
     * - equals / not_equals
     * - contains
     * - regex
     * - min / max (numeric)
     * - min_length / max_length
     * - in / not_in
     *
     * @param  list<array<string, mixed>>  $rules
     * @return list<string> error messages (empty = pass)
     */
    public function evaluate(mixed $value, array $rules, string $fieldLabel): array
    {
        $errors = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $type = (string) ($rule['type'] ?? '');
            $message = (string) ($rule['message'] ?? "Custom rule failed for {$fieldLabel}.");

            $failed = match ($type) {
                'equals' => $value != ($rule['value'] ?? null),
                'not_equals' => $value == ($rule['value'] ?? null),
                'contains' => ! is_string($value) || ! str_contains($value, (string) ($rule['value'] ?? '')),
                'regex' => ! is_string($value) || @preg_match((string) ($rule['pattern'] ?? '/.*/'), $value) !== 1,
                'min' => ! is_numeric($value) || (float) $value < (float) ($rule['value'] ?? 0),
                'max' => ! is_numeric($value) || (float) $value > (float) ($rule['value'] ?? 0),
                'min_length' => ! is_string($value) || mb_strlen($value) < (int) ($rule['value'] ?? 0),
                'max_length' => ! is_string($value) || mb_strlen($value) > (int) ($rule['value'] ?? 0),
                'in' => ! in_array($value, (array) ($rule['values'] ?? []), true),
                'not_in' => in_array($value, (array) ($rule['values'] ?? []), true),
                'required_if_empty_source' => false,
                default => false,
            };

            if ($failed) {
                $errors[] = $message;
            }
        }

        return $errors;
    }
}
