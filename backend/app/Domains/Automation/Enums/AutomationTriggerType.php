<?php

namespace App\Domains\Automation\Enums;

enum AutomationTriggerType: string
{
    case Event = 'event';
    case Schedule = 'schedule';
    case Time = 'time';

    public function label(): string
    {
        return match ($this) {
            self::Event => 'Event Based',
            self::Schedule => 'Scheduled',
            self::Time => 'Time Delayed',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
