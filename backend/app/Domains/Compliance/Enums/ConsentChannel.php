<?php

namespace App\Domains\Compliance\Enums;

enum ConsentChannel: string
{
    case Marketing = 'marketing';
    case Analytics = 'analytics';
    case PushNotification = 'push_notification';
    case Email = 'email';
    case Sms = 'sms';
    case Cookie = 'cookie';

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
            self::Marketing => 'Marketing Consent',
            self::Analytics => 'Analytics Consent',
            self::PushNotification => 'Push Notification Consent',
            self::Email => 'Email Consent',
            self::Sms => 'SMS Consent',
            self::Cookie => 'Cookie Consent',
        };
    }

    public function defaultCode(): string
    {
        return $this->value;
    }
}
