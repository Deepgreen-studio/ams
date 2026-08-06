<?php

namespace App\Domains\Compliance\Enums;

enum DpiaStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
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
            self::InProgress->value,
            self::PendingReview->value,
            self::Rejected->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InProgress => 'In Progress',
            self::PendingReview => 'Pending Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Archived => 'Archived',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::InProgress, self::PendingReview, self::Archived],
            self::InProgress => [self::PendingReview, self::Draft, self::Archived],
            self::PendingReview => [self::Approved, self::Rejected, self::InProgress],
            self::Approved => [self::Archived, self::InProgress],
            self::Rejected => [self::InProgress, self::Archived],
            self::Archived => [self::Draft],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
