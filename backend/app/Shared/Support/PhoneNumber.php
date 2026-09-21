<?php

namespace App\Shared\Support;

use App\Shared\Rules\E164PhoneNumber;

final class PhoneNumber
{
    public const E164_PATTERN = '/^\+[1-9]\d{6,14}$/';

    public const MAX_LENGTH = 16;

    /**
     * Compact a user-supplied phone value to digits with a leading +.
     * Returns null for blank input. Invalid characters are left intact so
     * validation can reject them instead of silently dropping data.
     */
    public static function canonicalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        $compact = preg_replace('/[\s\-\(\)\.\x{00A0}]/u', '', $trimmed) ?? '';

        if (str_starts_with($compact, '00')) {
            $compact = '+' . substr($compact, 2);
        }

        if (! preg_match('/^\+?[0-9]+$/', $compact)) {
            return $trimmed;
        }

        if (! str_starts_with($compact, '+')) {
            return $compact;
        }

        $digits = ltrim(substr($compact, 1), '0');

        if ($digits === '') {
            return $trimmed;
        }

        return '+' . $digits;
    }

    public static function isE164(?string $value): bool
    {
        return is_string($value) && preg_match(self::E164_PATTERN, $value) === 1;
    }

    /**
     * Normalized value for database storage. Blank becomes null.
     * Non-E.164 values are returned canonicalized so FormRequest can fail them.
     */
    public static function store(mixed $value): ?string
    {
        $canonical = self::canonicalize($value);

        if ($canonical === null || $canonical === '') {
            return null;
        }

        return $canonical;
    }

    /**
     * @return list<mixed>
     */
    public static function inputRules(): array
    {
        return ['nullable', 'string', 'max:' . self::MAX_LENGTH, new E164PhoneNumber];
    }
}
