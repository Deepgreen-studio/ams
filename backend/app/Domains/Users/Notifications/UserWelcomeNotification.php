<?php

namespace App\Domains\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->full_name ?: $notifiable->name;

        return (new MailMessage)
            ->subject('Welcome to '.config('app.name'))
            ->greeting('Hello '.$name.',')
            ->line('Your account has been created on the enterprise management platform.')
            ->line('You can sign in using your email address and the temporary password provided by your administrator.')
            ->action('Open Platform', rtrim((string) config('app.frontend_url'), '/').'/auth/login')
            ->line('If you did not expect this invitation, please contact your administrator.');
    }
}
