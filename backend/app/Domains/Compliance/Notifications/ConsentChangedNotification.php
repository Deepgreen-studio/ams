<?php

namespace App\Domains\Compliance\Notifications;

use App\Domains\Compliance\Models\UserConsent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConsentChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly UserConsent $consent,
        public readonly User $actor,
        public readonly string $changeType
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
        $label = $this->changeType === 'withdrawn' ? 'withdrawn' : 'updated';

        return (new MailMessage)
            ->subject('Consent '.$label.': '.($this->consent->consentType?->name ?? 'Preference'))
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('A consent preference has been '.$label.'.')
            ->line('Type: '.($this->consent->consentType?->name ?? '—'))
            ->line('Subject: '.($this->consent->subject_name ?: $this->consent->subject_email))
            ->line('Status: '.($this->consent->status?->label() ?? $this->consent->status))
            ->line('Version: '.$this->consent->consent_version)
            ->line('Updated by: '.$this->actor->full_name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'consent_'.$this->changeType,
            'consent_uuid' => $this->consent->uuid,
            'consent_type' => $this->consent->consentType?->code,
            'subject_email' => $this->consent->subject_email,
            'status' => $this->consent->status?->value,
            'granted' => $this->consent->granted,
            'consent_version' => $this->consent->consent_version,
            'actor_uuid' => $this->actor->uuid,
            'actor_name' => $this->actor->full_name,
        ];
    }
}
