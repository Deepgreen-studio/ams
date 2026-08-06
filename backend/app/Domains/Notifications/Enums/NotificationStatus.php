<?php

namespace App\Domains\Notifications\Enums;

enum NotificationStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Scheduled = 'scheduled';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Queued => 'Queued',
            self::Scheduled => 'Scheduled',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
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
