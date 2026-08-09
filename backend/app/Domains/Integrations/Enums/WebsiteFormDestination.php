<?php

namespace App\Domains\Integrations\Enums;

enum WebsiteFormDestination: string
{
    case Support = 'support';
    case SupportAndPrivacy = 'support_and_privacy';
    case PrivacyOnly = 'privacy_only';
    case ComplianceCase = 'compliance_case';
    case Breach = 'breach';
    case Dpia = 'dpia';

    public function createsSupportTicket(): bool
    {
        return match ($this) {
            self::Support, self::SupportAndPrivacy => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Support => 'Support only',
            self::SupportAndPrivacy => 'Support + Compliance Privacy Request',
            self::PrivacyOnly => 'Compliance Privacy Request only',
            self::ComplianceCase => 'Compliance Cases',
            self::Breach => 'Compliance Breaches',
            self::Dpia => 'Compliance DPIA',
        };
    }
}
