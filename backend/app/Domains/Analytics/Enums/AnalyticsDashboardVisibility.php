<?php

namespace App\Domains\Analytics\Enums;

enum AnalyticsDashboardVisibility: string
{
    case Personal = 'personal';
    case Company = 'company';
    case Role = 'role';
    case Shared = 'shared';
    case Template = 'template';
    case System = 'system';

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
            self::Personal => 'Personal',
            self::Company => 'Company',
            self::Role => 'Role-based',
            self::Shared => 'Shared',
            self::Template => 'Template',
            self::System => 'System',
        };
    }
}
