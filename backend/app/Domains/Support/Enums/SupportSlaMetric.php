<?php

namespace App\Domains\Support\Enums;

enum SupportSlaMetric: string
{
    case Response = 'response';
    case Resolution = 'resolution';

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
            self::Response => 'Response SLA',
            self::Resolution => 'Resolution SLA',
        };
    }
}
