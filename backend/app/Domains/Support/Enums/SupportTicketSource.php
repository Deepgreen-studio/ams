<?php

namespace App\Domains\Support\Enums;

enum SupportTicketSource: string
{
    case Portal = 'portal';
    case Email = 'email';
    case Phone = 'phone';
    case Chat = 'chat';
    case Api = 'api';
    case Internal = 'internal';
    case Web = 'web';
    case Sms = 'sms';

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
            self::Portal => 'Portal',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Chat => 'Chat',
            self::Api => 'API',
            self::Internal => 'Internal',
            self::Web => 'Web',
            self::Sms => 'SMS',
        };
    }
}
