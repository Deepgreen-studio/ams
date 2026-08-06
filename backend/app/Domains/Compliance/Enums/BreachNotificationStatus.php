<?php

namespace App\Domains\Compliance\Enums;

enum BreachNotificationStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Acknowledged = 'acknowledged';

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
            self::Draft => 'Draft',
            self::Queued => 'Queued',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::Acknowledged => 'Acknowledged',
        };
    }
}
