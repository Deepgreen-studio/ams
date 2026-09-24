<?php

namespace App\Domains\Users\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserPasswordSetupNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->full_name ?: $notifiable->name ?: 'there';
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $email = urlencode((string) $notifiable->getEmailForPasswordReset());
        $url = $frontendUrl.'/auth/reset-password?token='.$this->token.'&email='.$email.'&setup=1';
        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Set your '.config('app.name').' password')
            ->greeting('Hello '.$name.',')
            ->line('An account has been created for you. Set a password, then sign in with your email address.')
            ->action('Set password', $url)
            ->line('This link expires in '.$expire.' minutes.')
            ->line('If you did not expect this email, you can ignore it.');
    }
}
