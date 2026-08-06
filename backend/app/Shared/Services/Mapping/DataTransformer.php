<?php

namespace App\Shared\Services\Mapping;

use App\Shared\Services\Mapping\DTOs\FieldMappingRuleDto;

class DataTransformer
{
    public function transform(mixed $value, FieldMappingRuleDto $rule): mixed
    {
        $type = strtolower($rule->transformType);
        $config = $rule->transformConfig;

        return match ($type) {
            'none', '' => $value,
            'trim' => is_string($value) ? trim($value) : $value,
            'uppercase' => is_string($value) ? mb_strtoupper($value) : $value,
            'lowercase' => is_string($value) ? mb_strtolower($value) : $value,
            'title_case' => is_string($value) ? mb_convert_case($value, MB_CASE_TITLE) : $value,
            'cast_string' => $value === null ? null : (string) $value,
            'cast_int' => $value === null || $value === '' ? null : (int) $value,
            'cast_float' => $value === null || $value === '' ? null : (float) $value,
            'cast_bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'date_format' => $this->formatDate($value, (string) ($config['format'] ?? 'Y-m-d')),
            'replace' => is_string($value)
                ? str_replace((string) ($config['search'] ?? ''), (string) ($config['replace'] ?? ''), $value)
                : $value,
            'prefix' => ($value === null || $value === '') ? $value : ((string) ($config['value'] ?? '')).$value,
            'suffix' => ($value === null || $value === '') ? $value : $value.((string) ($config['value'] ?? '')),
            'split_first' => $this->splitFirst($value, (string) ($config['delimiter'] ?? ' ')),
            'split_last' => $this->splitLast($value, (string) ($config['delimiter'] ?? ' ')),
            'lookup' => $this->lookup($value, (array) ($config['map'] ?? [])),
            'template' => $this->applyTemplate((string) ($config['template'] ?? '{value}'), $value),
            default => $value,
        };
    }

    protected function formatDate(mixed $value, string $format): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable((string) $value))->format($format);
        } catch (\Exception) {
            return $value;
        }
    }

    protected function splitFirst(mixed $value, string $delimiter): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }
        $parts = explode($delimiter, $value, 2);

        return $parts[0] ?? $value;
    }

    protected function splitLast(mixed $value, string $delimiter): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }
        $parts = explode($delimiter, $value);

        return $parts[array_key_last($parts)] ?? $value;
    }

    /**
     * @param  array<string|int, mixed>  $map
     */
    protected function lookup(mixed $value, array $map): mixed
    {
        $key = is_scalar($value) ? (string) $value : null;
        if ($key === null) {
            return $value;
        }

        return array_key_exists($key, $map) ? $map[$key] : ($map['*'] ?? $value);
    }

    protected function applyTemplate(string $template, mixed $value): string
    {
        return str_replace('{value}', (string) ($value ?? ''), $template);
    }
}
