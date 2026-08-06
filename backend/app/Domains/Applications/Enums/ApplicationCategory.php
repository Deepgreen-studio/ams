<?php

namespace App\Domains\Applications\Enums;

enum ApplicationCategory: string
{
    case Business = 'business';
    case Productivity = 'productivity';
    case Utilities = 'utilities';
    case Social = 'social';
    case Education = 'education';
    case Health = 'health';
    case Finance = 'finance';
    case Entertainment = 'entertainment';
    case Other = 'other';

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
            self::Business => 'Business',
            self::Productivity => 'Productivity',
            self::Utilities => 'Utilities',
            self::Social => 'Social',
            self::Education => 'Education',
            self::Health => 'Health',
            self::Finance => 'Finance',
            self::Entertainment => 'Entertainment',
            self::Other => 'Other',
        };
    }
}
