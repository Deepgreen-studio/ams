<?php

namespace App\Domains\Compliance\Enums;

enum ComplianceCaseStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case UnderReview = 'under_review';
    case Pending = 'pending';
    case Completed = 'completed';
    case Closed = 'closed';
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
            self::Open->value,
            self::InProgress->value,
            self::UnderReview->value,
            self::Pending->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function terminalValues(): array
    {
        return [
            self::Completed->value,
            self::Closed->value,
            self::Cancelled->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::UnderReview => 'Under Review',
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this->value, self::terminalValues(), true);
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::Completed, self::Closed], true);
    }
}
