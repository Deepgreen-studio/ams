<?php

namespace App\Domains\Roles\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reserved for future role-change notifications.
 */
class RoleAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $roleName) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Role assigned')
            ->line('You have been assigned the role: '.$this->roleName.'.');
    }
}
