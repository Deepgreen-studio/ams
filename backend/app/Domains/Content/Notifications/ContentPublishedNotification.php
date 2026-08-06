<?php

namespace App\Domains\Content\Notifications;

use App\Domains\Content\Models\Content;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContentPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Content $content
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->full_name ?: $notifiable->name;
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return (new MailMessage)
            ->subject('Content published: '.$this->content->title)
            ->greeting('Hello '.$name.',')
            ->line('The content "'.$this->content->title.'" has been published.')
            ->action('View Content', $frontendUrl.'/content/'.$this->content->uuid)
            ->line('You are receiving this because you created this content entry.');
    }
}
