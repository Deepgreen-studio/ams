<?php

namespace App\Domains\Support\Enums;

enum SupportTicketCategory: string
{
    case CustomerSupport = 'customer_support';
    case TechnicalSupport = 'technical_support';
    case BillingSupport = 'billing_support';
    case GeneralInquiry = 'general_inquiry';
    case BugReport = 'bug_report';
    case FeatureRequest = 'feature_request';
    case EmergencySupport = 'emergency_support';

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
            self::CustomerSupport => 'Customer Support',
            self::TechnicalSupport => 'Technical Support',
            self::BillingSupport => 'Billing Support',
            self::GeneralInquiry => 'General Inquiry',
            self::BugReport => 'Bug Report',
            self::FeatureRequest => 'Feature Request',
            self::EmergencySupport => 'Emergency Support',
        };
    }
}
