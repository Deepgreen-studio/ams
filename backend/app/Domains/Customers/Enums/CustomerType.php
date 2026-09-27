<?php

namespace App\Domains\Customers\Enums;

enum CustomerType: string
{
    case Individual = 'individual';
    case Business = 'business';
    case Enterprise = 'enterprise';

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
            self::Individual => 'Individual',
            self::Business => 'Organization — Business',
            self::Enterprise => 'Organization — Enterprise',
        };
    }

    /**
     * Business and Enterprise are organization customers. They share organization
     * fields and differ only by commercial category.
     */
    public function isOrganization(): bool
    {
        return $this === self::Business || $this === self::Enterprise;
    }

    public function organizationCategory(): ?string
    {
        return match ($this) {
            self::Business => 'business',
            self::Enterprise => 'enterprise',
            default => null,
        };
    }

    public function requiresCompanyName(): bool
    {
        return $this->isOrganization();
    }

    public function requiresPersonName(): bool
    {
        return $this === self::Individual;
    }
}
