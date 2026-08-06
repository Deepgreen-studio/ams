<?php

namespace App\Domains\Compliance\Enums;

enum PrivacyRequestStatus: string
{
    case Submitted = 'submitted';
    case IdentityPending = 'identity_pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

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
            self::Submitted->value,
            self::IdentityPending->value,
            self::UnderReview->value,
            self::Approved->value,
            self::InProgress->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function terminalValues(): array
    {
        return [
            self::Completed->value,
            self::Rejected->value,
            self::Cancelled->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::IdentityPending => 'Identity Pending',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this->value, self::terminalValues(), true);
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Submitted => [
                self::IdentityPending,
                self::UnderReview,
                self::Cancelled,
            ],
            self::IdentityPending => [
                self::UnderReview,
                self::Cancelled,
                self::Rejected,
            ],
            self::UnderReview => [
                self::Approved,
                self::Rejected,
                self::IdentityPending,
                self::Cancelled,
            ],
            self::Approved => [
                self::InProgress,
                self::Completed,
                self::Cancelled,
            ],
            self::InProgress => [
                self::Completed,
                self::Cancelled,
            ],
            self::Rejected => [
                self::UnderReview,
            ],
            self::Completed => [],
            self::Cancelled => [
                self::UnderReview,
            ],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
