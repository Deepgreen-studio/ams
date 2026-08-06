<?php

namespace App\Domains\Notifications\Enums;

enum NotificationEventKey: string
{
    case TicketCreated = 'support.ticket_created';
    case TicketAssigned = 'support.ticket_assigned';
    case ReplyAdded = 'support.reply_added';
    case StatusChanged = 'support.status_changed';
    case TicketClosed = 'support.ticket_closed';
    case SlaWarning = 'support.sla_warning';
    case Escalation = 'support.escalation';

    public function label(): string
    {
        return match ($this) {
            self::TicketCreated => 'Ticket Created',
            self::TicketAssigned => 'Ticket Assigned',
            self::ReplyAdded => 'Reply Added',
            self::StatusChanged => 'Status Changed',
            self::TicketClosed => 'Ticket Closed',
            self::SlaWarning => 'SLA Warning',
            self::Escalation => 'Escalation',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TicketCreated => 'When a new support ticket is created.',
            self::TicketAssigned => 'When a ticket is assigned to an agent.',
            self::ReplyAdded => 'When a public reply is added to a ticket.',
            self::StatusChanged => 'When a ticket status changes.',
            self::TicketClosed => 'When a ticket is closed.',
            self::SlaWarning => 'When a ticket SLA becomes at risk.',
            self::Escalation => 'When an SLA escalation is raised.',
        };
    }

    /**
     * @return list<string>
     */
    public function defaultVariables(): array
    {
        return [
            'ticket_number',
            'ticket_uuid',
            'subject',
            'priority',
            'status',
            'category',
            'actor_name',
            'recipient_name',
            'ticket_url',
            'message_preview',
            'from_status',
            'to_status',
            'sla_metric',
            'escalation_level',
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
