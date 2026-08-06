<?php

namespace App\Domains\Support\Enums;

enum SupportTicketMessageAuthorType: string
{
    case Agent = 'agent';
    case Customer = 'customer';
    case System = 'system';

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
            self::Agent => 'Agent',
            self::Customer => 'Customer',
            self::System => 'System',
        };
    }
}
