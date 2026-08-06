<?php

namespace App\Domains\Compliance\Notifications;

use App\Domains\Compliance\Models\PolicyDocument;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolicyDocumentStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PolicyDocument $policy,
        public readonly User $actor,
        public readonly string $eventType
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
            ->subject('Policy '.$this->eventType.': '.$this->policy->policy_number)
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('A policy document has been '.$this->eventType.'.')
            ->line('Number: '.$this->policy->policy_number)
            ->line('Title: '.$this->policy->title)
            ->line('Version: v'.$this->policy->current_version)
            ->line('Status: '.($this->policy->status?->label() ?? '—'))
            ->line('Updated by: '.$this->actor->full_name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'policy_'.$this->eventType,
            'policy_uuid' => $this->policy->uuid,
            'policy_number' => $this->policy->policy_number,
            'title' => $this->policy->title,
            'version' => $this->policy->current_version,
            'status' => $this->policy->status?->value,
            'actor_uuid' => $this->actor->uuid,
            'actor_name' => $this->actor->full_name,
        ];
    }
}
