<?php

namespace App\Domains\Integrations\Enums;

/**
 * Website / connected-app form intents for Support ↔ Compliance ingest routing.
 */
enum WebsiteFormIntent: string
{
    case Support = 'support';
    case Complaint = 'complaint';
    case Privacy = 'privacy';
    case AccountDisable = 'account_disable';
    case Chat = 'chat';
    case Sms = 'sms';
    case ComplianceCase = 'compliance_case';
    case Breach = 'breach';
    case Consent = 'consent';
    case Dpia = 'dpia';

    /**
     * @var list<string>
     */
    private const ALIASES = [
        'help' => 'support',
        'live_chat' => 'chat',
        'gdpr' => 'privacy',
        'data_deletion' => 'privacy',
        'data_correction' => 'privacy',
        'health_data' => 'privacy',
        'delete_data' => 'privacy',
        'case' => 'compliance_case',
        'data_breach' => 'breach',
        'consent_withdrawal' => 'consent',
    ];

    public static function tryFromAlias(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return null;
        }

        $normalized = self::ALIASES[$normalized] ?? $normalized;

        return self::tryFrom($normalized);
    }

    public function destination(): WebsiteFormDestination
    {
        return match ($this) {
            self::Support,
            self::Complaint,
            self::AccountDisable,
            self::Chat,
            self::Sms => WebsiteFormDestination::Support,
            self::Privacy => WebsiteFormDestination::SupportAndPrivacy,
            self::Consent => WebsiteFormDestination::PrivacyOnly,
            self::ComplianceCase => WebsiteFormDestination::ComplianceCase,
            self::Breach => WebsiteFormDestination::Breach,
            self::Dpia => WebsiteFormDestination::Dpia,
        };
    }

    public function createsSupportTicket(): bool
    {
        return $this->destination()->createsSupportTicket();
    }

    public function involvesPersonalData(): bool
    {
        return match ($this) {
            self::Privacy, self::Consent => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Support => 'Help / Support',
            self::Complaint => 'Complaint',
            self::Privacy => 'Privacy / GDPR / delete data',
            self::AccountDisable => 'Disable account',
            self::Chat => 'Live chat',
            self::Sms => 'SMS',
            self::ComplianceCase => 'Compliance case',
            self::Breach => 'Data breach report',
            self::Consent => 'Consent withdrawal',
            self::Dpia => 'DPIA request',
        };
    }
}
