<?php

namespace App\Domains\Compliance\Notifications;

use App\Domains\Compliance\Models\ComplianceCase;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplianceCaseAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ComplianceCase $case,
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
            ->subject('Compliance case assigned: '.$this->case->case_number)
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('A compliance case has been assigned to you.')
            ->line('Case: '.$this->case->case_number)
            ->line('Title: '.$this->case->title)
            ->line('Type: '.($this->case->case_type?->label() ?? $this->case->case_type))
            ->line('Priority: '.($this->case->priority?->label() ?? $this->case->priority))
            ->line('Assigned by: '.$this->actor->full_name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'compliance_case_assigned',
            'case_uuid' => $this->case->uuid,
            'case_number' => $this->case->case_number,
            'title' => $this->case->title,
            'case_type' => $this->case->case_type?->value ?? $this->case->case_type,
            'priority' => $this->case->priority?->value ?? $this->case->priority,
            'status' => $this->case->status?->value ?? $this->case->status,
            'actor_uuid' => $this->actor->uuid,
            'actor_name' => $this->actor->full_name,
        ];
    }
}
