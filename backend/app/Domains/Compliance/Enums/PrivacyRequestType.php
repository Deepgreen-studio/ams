<?php

namespace App\Domains\Compliance\Enums;

enum PrivacyRequestType: string
{
    case AccessRequest = 'access_request';
    case DataExport = 'data_export';
    case DataCorrection = 'data_correction';
    case DataDeletion = 'data_deletion';
    case RestrictProcessing = 'restrict_processing';
    case RightToObject = 'right_to_object';
    case ConsentWithdrawal = 'consent_withdrawal';
    case DataPortability = 'data_portability';

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
            self::AccessRequest => 'Access Request',
            self::DataExport => 'Data Export',
            self::DataCorrection => 'Data Correction',
            self::DataDeletion => 'Data Deletion',
            self::RestrictProcessing => 'Right to Restrict Processing',
            self::RightToObject => 'Right to Object',
            self::ConsentWithdrawal => 'Consent Withdrawal',
            self::DataPortability => 'Data Portability',
        };
    }

    public function requiresExport(): bool
    {
        return in_array($this, [self::AccessRequest, self::DataExport, self::DataPortability], true);
    }

    public function requiresDeletion(): bool
    {
        return $this === self::DataDeletion;
    }
}
