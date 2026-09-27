<?php

namespace App\Shared\Support;

use Closure;

class CountryCatalog
{
    public static function code(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z]{2}$/', $value) === 1) {
            $code = strtoupper($value);

            return self::isCode($code) ? $code : null;
        }

        foreach (self::codes() as $code) {
            if (strcasecmp(self::displayName($code), $value) === 0) {
                return $code;
            }
        }

        return null;
    }

    public static function name(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $code = self::code($value);

        return $code === null ? trim($value) : self::displayName($code);
    }

    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (self::code($value) === null) {
                $fail('Select a valid country.');
            }
        };
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        static $codes = null;

        if ($codes !== null) {
            return $codes;
        }

        if (extension_loaded('intl')) {
            $bundle = \ResourceBundle::create('en', 'ICUDATA-region');
            $countries = $bundle?->get('Countries');
            $resolved = [];
            if ($countries instanceof \ResourceBundle) {
                foreach ($countries as $code => $label) {
                    if (is_string($code) && strlen($code) === 2 && $code !== 'ZZ') {
                        $resolved[] = strtoupper($code);
                    }
                }
            }
            if ($resolved !== []) {
                return $codes = $resolved;
            }
        }

        return $codes = [
            'AE', 'AU', 'BD', 'CA', 'DE', 'FR', 'GB', 'IN', 'MY', 'NG', 'PK', 'SA', 'SG', 'US',
        ];
    }

    private static function isCode(string $code): bool
    {
        return in_array($code, self::codes(), true);
    }

    private static function displayName(string $code): string
    {
        if (extension_loaded('intl') && class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('_'.$code, 'en');
            if (is_string($name) && $name !== '' && $name !== $code) {
                return $name;
            }
        }

        return $code;
    }
}
