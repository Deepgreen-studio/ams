<?php

namespace App\Domains\Support\Enums;

enum SupportTicketAssignmentType: string
{
    case Manual = 'manual';
    case Auto = 'auto';
    case Department = 'department';
    case Team = 'team';
    case Agent = 'agent';

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
            self::Manual => 'Manual Assignment',
            self::Auto => 'Auto Assignment',
            self::Department => 'Department Assignment',
            self::Team => 'Team Assignment',
            self::Agent => 'Agent Assignment',
        };
    }
}
