<?php

namespace App\Domains\Compliance\Notifications;

use App\Domains\Compliance\Models\DpiaAssessment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DpiaStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly DpiaAssessment $assessment,
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
            ->subject('DPIA '.$this->eventType.': '.$this->assessment->assessment_number)
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('A DPIA assessment has been '.$this->eventType.'.')
            ->line('Number: '.$this->assessment->assessment_number)
            ->line('Title: '.$this->assessment->title)
            ->line('Status: '.($this->assessment->status?->label() ?? '—'))
            ->line('Updated by: '.$this->actor->full_name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'dpia_'.$this->eventType,
            'assessment_uuid' => $this->assessment->uuid,
            'assessment_number' => $this->assessment->assessment_number,
            'title' => $this->assessment->title,
            'status' => $this->assessment->status?->value,
            'actor_uuid' => $this->actor->uuid,
            'actor_name' => $this->actor->full_name,
        ];
    }
}
