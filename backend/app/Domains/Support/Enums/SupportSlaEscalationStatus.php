<?php

namespace App\Domains\Support\Enums;

enum SupportSlaEscalationStatus: string
{
    case Pending = 'pending';
    case Notified = 'notified';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';

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
            self::Pending => 'Pending',
            self::Notified => 'Notified',
            self::Acknowledged => 'Acknowledged',
            self::Resolved => 'Resolved',
            self::Cancelled => 'Cancelled',
        };
    }
}
