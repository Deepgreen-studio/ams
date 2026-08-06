<?php

namespace App\Domains\Compliance\Enums;

enum BreachNotificationChannel: string
{
    case Email = 'email';
    case Letter = 'letter';
    case Phone = 'phone';
    case Portal = 'portal';
    case Sms = 'sms';
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
            self::Email => 'Email',
            self::Letter => 'Letter',
            self::Phone => 'Phone',
            self::Portal => 'Portal',
            self::Sms => 'SMS',
            self::Other => 'Other',
        };
    }
}
