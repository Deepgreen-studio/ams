<?php

namespace App\Domains\Support\Listeners;

use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Services\NotificationDispatchService;
use App\Domains\Support\Enums\SupportTicketMessageVisibility;
use App\Domains\Support\Enums\SupportTicketSource;
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
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Services\SupportNotificationRecipientResolver;
use App\Models\User;
use Illuminate\Support\Str;

class PrepareSupportNotifications
{
    public function __construct(
        private readonly NotificationDispatchService $dispatchService,
        private readonly SupportNotificationRecipientResolver $recipientResolver,
    ) {}

    public function handleSupportTicketCreated(SupportTicketCreated $event): void
    {
        $this->send(
            NotificationEventKey::TicketCreated,
            $event->ticket,
            $event->actor,
            $this->ticketPayload($event->ticket, $event->actor),
            excludeActor: ! $this->isInboundComplaint($event->ticket),
        );
    }

    public function handleSupportTicketUpdated(SupportTicketUpdated $event): void {}

    public function handleSupportTicketDeleted(SupportTicketDeleted $event): void {}

    public function handleSupportTicketRestored(SupportTicketRestored $event): void {}

    public function handleSupportTicketAssigned(SupportTicketAssigned $event): void
    {
        $this->send(
            NotificationEventKey::TicketAssigned,
            $event->ticket,
            $event->actor,
            $this->ticketPayload($event->ticket, $event->actor),
            excludeActor: ! $this->isInboundComplaint($event->ticket),
        );
    }

    public function handleSupportTicketClosed(SupportTicketClosed $event): void
    {
        $this->send(
            NotificationEventKey::TicketClosed,
            $event->ticket,
            $event->actor,
            $this->ticketPayload($event->ticket, $event->actor)
        );
    }

    public function handleSupportTicketReopened(SupportTicketReopened $event): void
    {
        $this->send(
            NotificationEventKey::StatusChanged,
            $event->ticket,
            $event->actor,
            array_merge($this->ticketPayload($event->ticket, $event->actor), [
                'from_status' => 'closed',
                'to_status' => $event->ticket->status?->value ?? (string) $event->ticket->status,
            ])
        );
    }

    public function handleSupportTicketStatusChanged(SupportTicketStatusChanged $event): void
    {
        $this->send(
            NotificationEventKey::StatusChanged,
            $event->ticket,
            $event->actor,
            array_merge($this->ticketPayload($event->ticket, $event->actor), [
                'from_status' => $event->fromStatus,
                'to_status' => $event->toStatus,
            ])
        );
    }

    public function handleSupportTicketMessageCreated(SupportTicketMessageCreated $event): void
    {
        $message = $event->message->loadMissing('ticket');
        $visibility = $message->visibility?->value ?? $message->visibility;

        if ($visibility !== SupportTicketMessageVisibility::Public->value && $visibility !== 'public') {
            return;
        }

        $ticket = $message->ticket;
        if (! $ticket instanceof SupportTicket) {
            return;
        }

        $preview = Str::limit(trim(html_entity_decode(strip_tags((string) $message->body))), 160);

        $this->send(
            NotificationEventKey::ReplyAdded,
            $ticket,
            $event->actor,
            array_merge($this->ticketPayload($ticket, $event->actor), [
                'message_preview' => $preview,
            ])
        );
    }

    public function handleSupportTicketAttachmentUploaded(SupportTicketAttachmentUploaded $event): void {}

    public function handleSupportTicketSlaBreached(SupportTicketSlaBreached $event): void
    {
        // Breach uses sla warning channel with metric context (at-risk fires separately).
        $this->send(
            NotificationEventKey::SlaWarning,
            $event->ticket,
            $event->actor,
            array_merge($this->ticketPayload($event->ticket, $event->actor), [
                'sla_metric' => $event->metric,
            ])
        );
    }

    public function handleSupportTicketSlaWarning(SupportTicketSlaWarning $event): void
    {
        $this->send(
            NotificationEventKey::SlaWarning,
            $event->ticket,
            $event->actor,
            array_merge($this->ticketPayload($event->ticket, $event->actor), [
                'sla_metric' => $event->metric,
            ])
        );
    }

    public function handleSupportTicketSlaEscalated(SupportTicketSlaEscalated $event): void
    {
        $escalation = $event->escalation->loadMissing('ticket');
        $ticket = $escalation->ticket;
        if (! $ticket instanceof SupportTicket) {
            return;
        }

        $this->send(
            NotificationEventKey::Escalation,
            $ticket,
            $event->actor,
            array_merge($this->ticketPayload($ticket, $event->actor), [
                'sla_metric' => $escalation->metric?->value ?? $escalation->metric,
                'escalation_level' => $escalation->level?->label()
                    ?? $escalation->level?->value
                    ?? $escalation->level,
            ])
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(
        NotificationEventKey $eventKey,
        SupportTicket $ticket,
        ?User $actor,
        array $payload,
        bool $excludeActor = true,
    ): void {
        $recipients = $this->recipientResolver->forTicket($ticket, $actor, $excludeActor);
        if ($recipients->isEmpty()) {
            return;
        }

        $this->dispatchService->dispatch($eventKey, $recipients, $payload);
    }

    /**
     * Inbound tickets/complaints (webhook, SMS, email, etc.) use a system actor that often
     * also has support.manage — still notify that staff so the bell/center updates.
     */
    private function isInboundComplaint(SupportTicket $ticket): bool
    {
        $source = $ticket->source instanceof SupportTicketSource
            ? $ticket->source
            : SupportTicketSource::tryFrom((string) $ticket->source);

        return match ($source) {
            SupportTicketSource::Api,
            SupportTicketSource::Sms,
            SupportTicketSource::Email,
            SupportTicketSource::Web,
            SupportTicketSource::Chat,
            SupportTicketSource::Phone => true,
            default => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function ticketPayload(SupportTicket $ticket, ?User $actor): array
    {
        return [
            'ticket_uuid' => $ticket->uuid,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'priority' => $ticket->priority?->label() ?? $ticket->priority,
            'status' => $ticket->status?->label() ?? $ticket->status,
            'category' => $ticket->category?->label() ?? $ticket->category,
            'actor_name' => $actor?->full_name ?? 'System',
        ];
    }
}
