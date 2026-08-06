<?php

namespace App\Domains\Compliance\Enums;

enum RiskCategory: string
{
    case Privacy = 'privacy';
    case Security = 'security';
    case Operational = 'operational';
    case Legal = 'legal';
    case ThirdParty = 'third_party';
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
            self::Privacy => 'Privacy',
            self::Security => 'Security',
            self::Operational => 'Operational',
            self::Legal => 'Legal',
            self::ThirdParty => 'Third Party',
            self::Other => 'Other',
        };
    }
}
