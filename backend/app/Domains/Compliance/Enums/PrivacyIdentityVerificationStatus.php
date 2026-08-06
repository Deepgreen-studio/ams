<?php

namespace App\Domains\Compliance\Enums;

enum PrivacyIdentityVerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Failed = 'failed';
    case NotRequired = 'not_required';

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
            self::Pending => 'Pending',
            self::Verified => 'Verified',
            self::Failed => 'Failed',
            self::NotRequired => 'Not Required',
        };
    }

    public function isVerified(): bool
    {
        return in_array($this, [self::Verified, self::NotRequired], true);
    }
}
