<?php

namespace App\Domains\Compliance\Enums;

enum ComplianceCaseType: string
{
    case Gdpr = 'gdpr';
    case UkGdpr = 'uk_gdpr';
    case PrivacyRequest = 'privacy_request';
    case ComplianceCase = 'compliance_case';
    case RiskRegister = 'risk_register';
    case AuditCompliance = 'audit_compliance';
    case Iso27001 = 'iso_27001';
    case Soc2 = 'soc2';
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
            self::Gdpr => 'GDPR',
            self::UkGdpr => 'UK GDPR',
            self::PrivacyRequest => 'Privacy Request',
            self::ComplianceCase => 'Compliance Case',
            self::RiskRegister => 'Risk Register',
            self::AuditCompliance => 'Audit Compliance',
            self::Iso27001 => 'ISO 27001',
            self::Soc2 => 'SOC 2',
            self::Other => 'Other',
        };
    }
}
