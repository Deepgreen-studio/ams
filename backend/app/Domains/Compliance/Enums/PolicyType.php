<?php

namespace App\Domains\Compliance\Enums;

enum PolicyType: string
{
    case PrivacyPolicy = 'privacy_policy';
    case Terms = 'terms';
    case CookiePolicy = 'cookie_policy';
    case SecurityPolicy = 'security_policy';
    case InternalPolicy = 'internal_policy';
    case EmployeeHandbook = 'employee_handbook';
    case ComplianceDocument = 'compliance_document';

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
            self::PrivacyPolicy => 'Privacy Policy',
            self::Terms => 'Terms & Conditions',
            self::CookiePolicy => 'Cookie Policy',
            self::SecurityPolicy => 'Security Policy',
            self::InternalPolicy => 'Internal Policy',
            self::EmployeeHandbook => 'Employee Handbook',
            self::ComplianceDocument => 'Compliance Document',
        };
    }

    public function preferredContentTypeSlug(): ?string
    {
        return match ($this) {
            self::PrivacyPolicy => 'privacy',
            self::Terms => 'terms',
            self::CookiePolicy => 'privacy',
            default => null,
        };
    }
}
