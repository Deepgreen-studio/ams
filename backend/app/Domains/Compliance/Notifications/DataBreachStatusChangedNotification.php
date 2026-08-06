<?php

namespace App\Domains\Compliance\Notifications;

use App\Domains\Compliance\Models\DataBreach;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataBreachStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly DataBreach $breach,
        public readonly User $actor,
        public readonly ?string $previousStatus = null
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
            ->subject('Data breach status updated: '.$this->breach->breach_number)
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('A data breach status has changed.')
            ->line('Number: '.$this->breach->breach_number)
            ->line('From: '.($this->previousStatus ?: '—'))
            ->line('To: '.($this->breach->status?->label() ?? '—'))
            ->line('Updated by: '.$this->actor->full_name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'data_breach_status_changed',
            'breach_uuid' => $this->breach->uuid,
            'breach_number' => $this->breach->breach_number,
            'from' => $this->previousStatus,
            'to' => $this->breach->status?->value,
            'actor_uuid' => $this->actor->uuid,
            'actor_name' => $this->actor->full_name,
        ];
    }
}
