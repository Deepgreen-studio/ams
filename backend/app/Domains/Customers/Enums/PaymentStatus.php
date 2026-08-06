<?php

namespace App\Domains\Customers\Enums;

enum PaymentStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PastDue = 'past_due';

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
            self::NotRequired => 'Not Required',
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
            self::PastDue => 'Past Due',
        };
    }
}
