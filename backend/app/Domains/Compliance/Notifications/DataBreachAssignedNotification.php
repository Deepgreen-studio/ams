<?php

namespace App\Domains\Compliance\Notifications;

use App\Domains\Compliance\Models\DataBreach;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataBreachAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly DataBreach $breach,
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
            ->subject('Data breach assigned: '.$this->breach->breach_number)
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('A data breach incident has been assigned to you.')
            ->line('Number: '.$this->breach->breach_number)
            ->line('Title: '.$this->breach->title)
            ->line('Severity: '.($this->breach->severity?->label() ?? '—'))
            ->line('Assigned by: '.$this->actor->full_name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'data_breach_assigned',
            'breach_uuid' => $this->breach->uuid,
            'breach_number' => $this->breach->breach_number,
            'title' => $this->breach->title,
            'severity' => $this->breach->severity?->value,
            'actor_uuid' => $this->actor->uuid,
            'actor_name' => $this->actor->full_name,
        ];
    }
}
