<?php

namespace App\Domains\Compliance\Enums;

enum PrivacyRequestDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case PartiallyApproved = 'partially_approved';
    case Withdrawn = 'withdrawn';

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
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::PartiallyApproved => 'Partially Approved',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
