<?php

namespace App\Domains\Automation\Enums;

enum AutomationEventKey: string
{
    case TicketCreated = 'support.ticket_created';
    case TicketAssigned = 'support.ticket_assigned';
    case TicketClosed = 'support.ticket_closed';
    case CustomerCreated = 'customer.created';
    case ApplicationCreated = 'application.created';
    case ApplicationReleaseDeployed = 'application.release_deployed';

    public function label(): string
    {
        return match ($this) {
            self::TicketCreated => 'When Ticket Created',
            self::TicketAssigned => 'When Ticket Assigned',
            self::TicketClosed => 'When Ticket Closed',
            self::CustomerCreated => 'When Customer Created',
            self::ApplicationCreated => 'When Application Created',
            self::ApplicationReleaseDeployed => 'When Application Released',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TicketCreated => 'Fires when a support ticket is created.',
            self::TicketAssigned => 'Fires when a support ticket is assigned.',
            self::TicketClosed => 'Fires when a support ticket is closed.',
            self::CustomerCreated => 'Fires when a customer is created.',
            self::ApplicationCreated => 'Fires when an application is created.',
            self::ApplicationReleaseDeployed => 'Fires when an application release is deployed.',
        };
    }

    /**
     * @return list<string>
     */
    public function sampleFields(): array
    {
        return match ($this) {
            self::TicketCreated, self::TicketAssigned, self::TicketClosed => [
                'priority', 'status', 'category', 'company_id', 'assigned_to', 'subject',
            ],
            self::CustomerCreated => [
                'customer_type', 'status', 'company_id', 'email',
            ],
            self::ApplicationCreated => [
                'platform', 'status', 'company_id', 'name',
            ],
            self::ApplicationReleaseDeployed => [
                'platform', 'version', 'application_id', 'company_id',
            ],
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
