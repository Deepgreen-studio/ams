<?php

namespace App\Domains\Workflows\Enums;

enum WorkflowLogAction: string
{
    case Started = 'started';
    case Advanced = 'advanced';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Escalated = 'escalated';
    case TimedOut = 'timed_out';
    case Commented = 'commented';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Started => 'Started',
            self::Advanced => 'Advanced',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Escalated => 'Escalated',
            self::TimedOut => 'Timed Out',
            self::Commented => 'Commented',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
