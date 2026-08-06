<?php

namespace App\Domains\Applications\Enums;

enum ApplicationMonitoringAlertOperator: string
{
    case Gt = 'gt';
    case Gte = 'gte';
    case Lt = 'lt';
    case Lte = 'lte';
    case Eq = 'eq';

    public function label(): string
    {
        return match ($this) {
            self::Gt => 'Greater than',
            self::Gte => 'Greater than or equal',
            self::Lt => 'Less than',
            self::Lte => 'Less than or equal',
            self::Eq => 'Equal',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
