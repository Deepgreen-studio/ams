<?php

namespace App\Domains\Integrations\Enums;

enum ConnectionRequestType: string
{
    case ConnectionTest = 'connection_test';
    case AuthenticationTest = 'authentication_test';
    case Request = 'request';
    case Upload = 'upload';
    case Download = 'download';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
