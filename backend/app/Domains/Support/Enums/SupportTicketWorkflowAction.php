<?php

namespace App\Domains\Support\Enums;

enum SupportTicketWorkflowAction: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case Assigned = 'assigned';
    case Reopened = 'reopened';
    case Closed = 'closed';
    case PriorityChanged = 'priority_changed';
    case EscalatedToCompliance = 'escalated_to_compliance';

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
            self::Created => 'Created',
            self::StatusChanged => 'Status Changed',
            self::Assigned => 'Assigned',
            self::Reopened => 'Reopened',
            self::Closed => 'Closed',
            self::PriorityChanged => 'Priority Changed',
            self::EscalatedToCompliance => 'Escalated to Compliance',
        };
    }
}
