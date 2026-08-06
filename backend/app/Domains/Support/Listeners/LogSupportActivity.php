<?php

namespace App\Domains\Support\Listeners;

use App\Domains\Support\Events\SupportTicketAssigned;
use App\Domains\Support\Events\SupportTicketAttachmentUploaded;
use App\Domains\Support\Events\SupportTicketClosed;
use App\Domains\Support\Events\SupportTicketCreated;
use App\Domains\Support\Events\SupportTicketDeleted;
use App\Domains\Support\Events\SupportTicketMessageCreated;
use App\Domains\Support\Events\SupportTicketReopened;
use App\Domains\Support\Events\SupportTicketRestored;
use App\Domains\Support\Events\SupportTicketSlaBreached;
use App\Domains\Support\Events\SupportTicketSlaEscalated;
use App\Domains\Support\Events\SupportTicketSlaWarning;
use App\Domains\Support\Events\SupportTicketStatusChanged;
use App\Domains\Support\Events\SupportTicketUpdated;
use Illuminate\Http\Request;

class LogSupportActivity
{
    public function __construct(
        private readonly ?Request $request = null
    ) {}

    public function handleSupportTicketCreated(SupportTicketCreated $event): void
    {
        $this->log($event->actor, $event->ticket, 'support_ticket_created', 'Support ticket created');
    }

    public function handleSupportTicketUpdated(SupportTicketUpdated $event): void
    {
        $this->log($event->actor, $event->ticket, 'support_ticket_updated', 'Support ticket updated');
    }

    public function handleSupportTicketDeleted(SupportTicketDeleted $event): void
    {
        $this->log($event->actor, $event->ticket, 'support_ticket_deleted', 'Support ticket archived');
    }

    public function handleSupportTicketRestored(SupportTicketRestored $event): void
    {
        $this->log($event->actor, $event->ticket, 'support_ticket_restored', 'Support ticket restored');
    }

    public function handleSupportTicketAssigned(SupportTicketAssigned $event): void
    {
        $this->log($event->actor, $event->ticket, 'support_ticket_assigned', 'Support ticket assigned', [
            'assigned_to' => $event->ticket->assigned_to,
            'assignment_type' => $event->ticket->assignment_type?->value ?? $event->ticket->assignment_type,
            'department_id' => $event->ticket->department_id,
            'team_id' => $event->ticket->team_id,
        ]);
    }

    public function handleSupportTicketClosed(SupportTicketClosed $event): void
    {
        $this->log($event->actor, $event->ticket, 'support_ticket_closed', 'Support ticket closed');
    }

    public function handleSupportTicketReopened(SupportTicketReopened $event): void
    {
        $this->log($event->actor, $event->ticket, 'support_ticket_reopened', 'Support ticket reopened');
    }

    public function handleSupportTicketStatusChanged(SupportTicketStatusChanged $event): void
    {
        $this->log($event->actor, $event->ticket, 'support_ticket_status_changed', 'Support ticket status changed', [
            'from_status' => $event->fromStatus,
            'to_status' => $event->toStatus,
            'comments' => $event->comments,
        ]);
    }

    public function handleSupportTicketMessageCreated(SupportTicketMessageCreated $event): void
    {
        $this->log($event->actor, $event->message->ticket, 'support_ticket_message_created', 'Support message posted', [
            'message_uuid' => $event->message->uuid,
            'visibility' => $event->message->visibility?->value ?? $event->message->visibility,
        ]);
    }

    public function handleSupportTicketAttachmentUploaded(SupportTicketAttachmentUploaded $event): void
    {
        $this->log($event->actor, $event->attachment->ticket, 'support_ticket_attachment_uploaded', 'Support attachment uploaded', [
            'attachment_uuid' => $event->attachment->uuid,
            'filename' => $event->attachment->original_filename,
            'attachment_type' => $event->attachment->attachment_type?->value ?? $event->attachment->attachment_type,
        ]);
    }

    public function handleSupportTicketSlaBreached(SupportTicketSlaBreached $event): void
    {
        $this->log($event->actor, $event->ticket, 'support_ticket_sla_breached', 'Support ticket SLA breached', [
            'metric' => $event->metric,
            'sla_status' => $event->ticket->sla_status?->value ?? $event->ticket->sla_status,
        ]);
    }

    public function handleSupportTicketSlaWarning(SupportTicketSlaWarning $event): void
    {
        $this->log($event->actor, $event->ticket, 'support_ticket_sla_warning', 'Support ticket SLA at risk', [
            'metric' => $event->metric,
            'sla_status' => $event->ticket->sla_status?->value ?? $event->ticket->sla_status,
        ]);
    }

    public function handleSupportTicketSlaEscalated(SupportTicketSlaEscalated $event): void
    {
        $ticket = $event->escalation->ticket ?? $event->escalation->loadMissing('ticket')->ticket;
        if (! $ticket) {
            return;
        }

        $this->log($event->actor, $ticket, 'support_ticket_sla_escalated', 'Support ticket SLA escalated', [
            'level' => $event->escalation->level?->value ?? $event->escalation->level,
            'trigger' => $event->escalation->trigger?->value ?? $event->escalation->trigger,
            'escalation_uuid' => $event->escalation->uuid,
        ]);
    }

    protected function log(
        $actor,
        $ticket,
        string $eventName,
        string $description,
        array $extra = []
    ): void {
        activity('support')
            ->causedBy($actor)
            ->performedOn($ticket)
            ->withProperties(array_merge([
                'event' => $eventName,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status?->value ?? $ticket->status,
                'priority' => $ticket->priority?->value ?? $ticket->priority,
                'ip' => $this->request?->ip(),
                'user_agent' => $this->request?->userAgent(),
            ], $extra))
            ->log($description);
    }
}
