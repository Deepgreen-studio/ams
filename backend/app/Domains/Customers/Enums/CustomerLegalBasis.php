<?php

namespace App\Domains\Customers\Enums;

enum CustomerLegalBasis: string
{
    case Consent = 'consent';
    case Contract = 'contract';
    case LegalObligation = 'legal_obligation';
    case LegitimateInterests = 'legitimate_interests';
    case VitalInterests = 'vital_interests';
    case PublicTask = 'public_task';

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
            self::Consent => 'Consent',
            self::Contract => 'Contract',
            self::LegalObligation => 'Legal obligation',
            self::LegitimateInterests => 'Legitimate interests',
            self::VitalInterests => 'Vital interests',
            self::PublicTask => 'Public task',
        };
    }
}
