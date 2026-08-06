<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Enums\NotificationChannel;
use App\Domains\Notifications\Enums\NotificationEventKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class TemplatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $laravelChannels
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $databaseData
     */
    public function __construct(
        public readonly NotificationEventKey $eventKey,
        public readonly array $laravelChannels,
        public readonly array $payload,
        public readonly array $databaseData,
        public readonly ?string $mailSubject = null,
        public readonly ?string $mailBody = null,
        public readonly ?string $inAppTitle = null,
        public readonly ?string $inAppBody = null,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->laravelChannels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->mailSubject ?: $this->eventKey->label();
        $body = $this->mailBody ?: 'You have a new notification.';
        $name = $notifiable->full_name ?? $notifiable->name ?? 'there';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$name.',')
            ->line(new HtmlString($body));

        if (! blank($this->payload['ticket_url'] ?? null)) {
            $mail->action('Open ticket', (string) $this->payload['ticket_url']);
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return array_merge($this->databaseData, [
            'event_key' => $this->eventKey->value,
            'title' => $this->inAppTitle ?: $this->eventKey->label(),
            'body' => $this->inAppBody ?: ($this->payload['subject'] ?? $this->eventKey->label()),
            'channel' => NotificationChannel::InApp->value,
        ]);
    }
}
