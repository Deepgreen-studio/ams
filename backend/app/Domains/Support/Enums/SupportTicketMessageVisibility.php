<?php

namespace App\Domains\Support\Enums;

enum SupportTicketMessageVisibility: string
{
    case Public = 'public';
    case Private = 'private';
    case Internal = 'internal';

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
            self::Public => 'Public Reply',
            self::Private => 'Private Reply',
            self::Internal => 'Internal Note',
        };
    }

    public function isStaffOnly(): bool
    {
        return in_array($this, [self::Private, self::Internal], true);
    }
}
