<?php

namespace App\Domains\Compliance\Notifications;

use App\Domains\Compliance\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrivacyRequestStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PrivacyRequest $privacyRequest,
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
            ->subject('Privacy request updated: '.$this->privacyRequest->request_number)
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('A privacy request status has changed.')
            ->line('Request: '.$this->privacyRequest->request_number)
            ->line('From: '.($this->previousStatus ?? '—'))
            ->line('To: '.($this->privacyRequest->status?->label() ?? $this->privacyRequest->status))
            ->line('Updated by: '.$this->actor->full_name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'privacy_request_status_changed',
            'privacy_request_uuid' => $this->privacyRequest->uuid,
            'request_number' => $this->privacyRequest->request_number,
            'from_status' => $this->previousStatus,
            'to_status' => $this->privacyRequest->status?->value,
            'actor_uuid' => $this->actor->uuid,
            'actor_name' => $this->actor->full_name,
        ];
    }
}
