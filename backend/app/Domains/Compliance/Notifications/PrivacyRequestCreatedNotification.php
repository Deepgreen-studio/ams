<?php

namespace App\Domains\Compliance\Notifications;

use App\Domains\Compliance\Models\PrivacyRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrivacyRequestCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PrivacyRequest $privacyRequest,
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
            ->subject('New privacy request '.$this->privacyRequest->request_number)
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('A new GDPR/privacy request has been submitted.')
            ->line('Request: '.$this->privacyRequest->request_number)
            ->line('Type: '.($this->privacyRequest->request_type?->label() ?? $this->privacyRequest->request_type))
            ->line('Requester: '.$this->privacyRequest->requester_name)
            ->line('Created by: '.$this->actor->full_name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'privacy_request_created',
            'privacy_request_uuid' => $this->privacyRequest->uuid,
            'request_number' => $this->privacyRequest->request_number,
            'request_type' => $this->privacyRequest->request_type?->value,
            'requester_name' => $this->privacyRequest->requester_name,
            'status' => $this->privacyRequest->status?->value,
            'actor_uuid' => $this->actor->uuid,
            'actor_name' => $this->actor->full_name,
        ];
    }
}
