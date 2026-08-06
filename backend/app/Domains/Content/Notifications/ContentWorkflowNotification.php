<?php

namespace App\Domains\Content\Notifications;

use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentWorkflowHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContentWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Content $content,
        public readonly ContentWorkflowHistory $history
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->full_name ?: $notifiable->name;
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $action = str_replace('_', ' ', (string) $this->history->action);

        return (new MailMessage)
            ->subject('Content workflow: '.$this->content->title)
            ->greeting('Hello '.$name.',')
            ->line('Content "'.$this->content->title.'" was '.$action.'.')
            ->line('Status: '.($this->history->from_status ?: '—').' → '.$this->history->to_status)
            ->when(filled($this->history->comments), fn (MailMessage $mail) => $mail->line('Comments: '.$this->history->comments))
            ->action('Open review', $frontendUrl.'/content/'.$this->content->uuid.'/review')
            ->line('You are receiving this because of your content workflow role.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'content_workflow',
            'content_uuid' => $this->content->uuid,
            'content_title' => $this->content->title,
            'action' => $this->history->action,
            'from_status' => $this->history->from_status,
            'to_status' => $this->history->to_status,
            'approval_level' => $this->history->approval_level,
            'comments' => $this->history->comments,
            'acted_by' => $this->history->acted_by,
        ];
    }
}
