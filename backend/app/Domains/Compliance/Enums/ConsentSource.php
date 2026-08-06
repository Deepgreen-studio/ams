<?php

namespace App\Domains\Compliance\Enums;

enum ConsentSource: string
{
    case Web = 'web';
    case Mobile = 'mobile';
    case Api = 'api';
    case PreferenceCenter = 'preference_center';
    case Admin = 'admin';
    case Import = 'import';
    case CookieBanner = 'cookie_banner';

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
            self::Web => 'Web',
            self::Mobile => 'Mobile',
            self::Api => 'API',
            self::PreferenceCenter => 'Preference Center',
            self::Admin => 'Admin',
            self::Import => 'Import',
            self::CookieBanner => 'Cookie Banner',
        };
    }
}
