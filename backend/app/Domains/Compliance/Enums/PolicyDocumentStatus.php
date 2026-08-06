<?php

namespace App\Domains\Compliance\Enums;

enum PolicyDocumentStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Approved = 'approved';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return [
            self::Draft->value,
            self::Review->value,
            self::Approved->value,
            self::Published->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Review => 'Review',
            self::Approved => 'Approved',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Review, self::Archived],
            self::Review => [self::Approved, self::Draft, self::Archived],
            self::Approved => [self::Published, self::Draft, self::Archived],
            self::Published => [self::Archived, self::Draft],
            self::Archived => [self::Draft],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
