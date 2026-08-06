<?php

namespace App\Domains\Workflows\Enums;

enum WorkflowInstanceStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case TimedOut = 'timed_out';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::TimedOut => 'Timed Out',
            self::Completed => 'Completed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Approved,
            self::Rejected,
            self::Cancelled,
            self::TimedOut,
            self::Completed,
        ], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
