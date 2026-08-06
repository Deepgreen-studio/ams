<?php

namespace App\Domains\Support\Enums;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case WaitingForCustomer = 'waiting_for_customer';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Reopened = 'reopened';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public static function kanbanColumns(): array
    {
        return [
            self::Open,
            self::Pending,
            self::InProgress,
            self::WaitingForCustomer,
            self::Resolved,
            self::Reopened,
            self::Closed,
            self::Cancelled,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::WaitingForCustomer => 'Waiting for Customer',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Reopened => 'Reopened',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Cancelled], true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [
                self::Pending,
                self::InProgress,
                self::WaitingForCustomer,
                self::Resolved,
                self::Cancelled,
                self::Closed,
            ],
            self::Pending => [
                self::Open,
                self::InProgress,
                self::WaitingForCustomer,
                self::Resolved,
                self::Cancelled,
                self::Closed,
            ],
            self::InProgress => [
                self::Pending,
                self::WaitingForCustomer,
                self::Resolved,
                self::Cancelled,
                self::Closed,
            ],
            self::WaitingForCustomer => [
                self::InProgress,
                self::Pending,
                self::Resolved,
                self::Cancelled,
                self::Closed,
            ],
            self::Resolved => [
                self::Closed,
                self::Reopened,
            ],
            self::Closed => [
                self::Reopened,
            ],
            self::Reopened => [
                self::InProgress,
                self::Pending,
                self::WaitingForCustomer,
                self::Resolved,
                self::Cancelled,
                self::Closed,
            ],
            self::Cancelled => [
                self::Reopened,
            ],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
