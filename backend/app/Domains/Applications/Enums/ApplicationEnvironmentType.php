<?php

namespace App\Domains\Applications\Enums;

enum ApplicationEnvironmentType: string
{
    case Development = 'development';
    case Testing = 'testing';
    case Staging = 'staging';
    case Production = 'production';
    case Sandbox = 'sandbox';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Development => 'Development',
            self::Testing => 'Testing',
            self::Staging => 'Staging',
            self::Production => 'Production',
            self::Sandbox => 'Sandbox',
        };
    }
}
