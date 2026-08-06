<?php

namespace App\Domains\Scheduler\Enums;

enum ScheduledJobType: string
{
    case Cron = 'cron';
    case Recurring = 'recurring';
    case OneTime = 'one_time';
    case Delayed = 'delayed';
    case Queue = 'queue';

    public function label(): string
    {
        return match ($this) {
            self::Cron => 'Cron Job',
            self::Recurring => 'Recurring Job',
            self::OneTime => 'One-Time Job',
            self::Delayed => 'Delayed Job',
            self::Queue => 'Queue Job',
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
