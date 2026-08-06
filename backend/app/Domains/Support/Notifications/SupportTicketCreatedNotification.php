<?php

namespace App\Domains\Support\Notifications;

use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly User $actor
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New support ticket '.$this->ticket->ticket_number)
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('A new support ticket has been created.')
            ->line('Ticket: '.$this->ticket->ticket_number)
            ->line('Subject: '.$this->ticket->subject)
            ->line('Priority: '.($this->ticket->priority?->label() ?? $this->ticket->priority))
            ->line('Category: '.($this->ticket->category?->label() ?? $this->ticket->category))
            ->line('Created by: '.$this->actor->full_name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'support_ticket_created',
            'ticket_uuid' => $this->ticket->uuid,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'priority' => $this->ticket->priority?->value ?? $this->ticket->priority,
            'category' => $this->ticket->category?->value ?? $this->ticket->category,
            'status' => $this->ticket->status?->value ?? $this->ticket->status,
            'actor_uuid' => $this->actor->uuid,
            'actor_name' => $this->actor->full_name,
        ];
    }
}
