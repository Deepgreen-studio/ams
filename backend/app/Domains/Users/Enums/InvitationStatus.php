<?php

namespace App\Domains\Users\Enums;

enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Expired = 'expired';

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
            self::Pending => 'Pending Invitation',
            self::Accepted => 'Accepted',
            self::Expired => 'Expired',
        };
    }
}
