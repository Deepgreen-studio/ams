<?php

namespace App\Domains\Authentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Placeholder welcome notification for future onboarding flows.
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to AMS')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Welcome to the Enterprise Application Management System.')
            ->line('Your account is ready. Sign in to get started.')
            ->action('Open AMS', (string) config('app.frontend_url'))
            ->line('Thank you for using our platform.');
    }
}
