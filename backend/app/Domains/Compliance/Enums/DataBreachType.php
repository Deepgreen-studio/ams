<?php

namespace App\Domains\Compliance\Enums;

enum DataBreachType: string
{
    case UnauthorizedAccess = 'unauthorized_access';
    case DataLoss = 'data_loss';
    case Ransomware = 'ransomware';
    case Phishing = 'phishing';
    case InsiderThreat = 'insider_threat';
    case Misconfiguration = 'misconfiguration';
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
            self::UnauthorizedAccess => 'Unauthorized Access',
            self::DataLoss => 'Data Loss',
            self::Ransomware => 'Ransomware',
            self::Phishing => 'Phishing',
            self::InsiderThreat => 'Insider Threat',
            self::Misconfiguration => 'Misconfiguration',
            self::ThirdParty => 'Third Party',
            self::Other => 'Other',
        };
    }
}
