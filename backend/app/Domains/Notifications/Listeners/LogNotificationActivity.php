<?php

namespace App\Domains\Notifications\Listeners;

use App\Domains\Notifications\Events\NotificationChannelUpdated;
use App\Domains\Notifications\Events\NotificationCreated;
use App\Domains\Notifications\Events\NotificationDeleted;
use App\Domains\Notifications\Events\NotificationPreferencesUpdated;
use App\Domains\Notifications\Events\NotificationRead;
use App\Domains\Notifications\Events\NotificationTemplateApproved;
use App\Domains\Notifications\Events\NotificationTemplateCreated;
use App\Domains\Notifications\Events\NotificationTemplateDeleted;
use App\Domains\Notifications\Events\NotificationTemplatePublished;
use App\Domains\Notifications\Events\NotificationTemplateRejected;
use App\Domains\Notifications\Events\NotificationTemplateSubmitted;
use App\Domains\Notifications\Events\NotificationTemplateUpdated;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LogNotificationActivity
{
    public function __construct(
        private readonly ?Request $request = null,
    ) {}

    public function handleNotificationCreated(NotificationCreated $event): void
    {
        $this->log($event->actor, $event->notification, 'notification_created', 'Notification created');
    }

    public function handleNotificationRead(NotificationRead $event): void
    {
        if ($event->notification) {
            $this->log($event->actor, $event->notification, 'notification_read', 'Notification marked as read');

            return;
        }

        activity('notifications')
            ->causedBy($event->actor)
            ->withProperties($this->context([
                'marked_count' => $event->count,
            ]))
            ->event('notification_read_all')
            ->log('All notifications marked as read');
    }

    public function handleNotificationDeleted(NotificationDeleted $event): void
    {
        $this->log($event->actor, $event->notification, 'notification_deleted', 'Notification deleted');
    }

    public function handleNotificationTemplateCreated(NotificationTemplateCreated $event): void
    {
        $this->log($event->actor, $event->template, 'notification_template_created', 'Notification template created');
    }

    public function handleNotificationTemplateUpdated(NotificationTemplateUpdated $event): void
    {
        $this->log($event->actor, $event->template, 'notification_template_updated', 'Notification template updated');
    }

    public function handleNotificationTemplateDeleted(NotificationTemplateDeleted $event): void
    {
        $this->log($event->actor, $event->template, 'notification_template_deleted', 'Notification template deleted');
    }

    public function handleNotificationTemplateSubmitted(NotificationTemplateSubmitted $event): void
    {
        $this->log($event->actor, $event->template, 'notification_template_submitted', 'Notification template submitted for review');
    }

    public function handleNotificationTemplateApproved(NotificationTemplateApproved $event): void
    {
        $this->log($event->actor, $event->template, 'notification_template_approved', 'Notification template approved');
    }

    public function handleNotificationTemplateRejected(NotificationTemplateRejected $event): void
    {
        $this->log($event->actor, $event->template, 'notification_template_rejected', 'Notification template rejected');
    }

    public function handleNotificationTemplatePublished(NotificationTemplatePublished $event): void
    {
        $this->log($event->actor, $event->template, 'notification_template_published', 'Notification template published');
    }

    public function handleNotificationChannelUpdated(NotificationChannelUpdated $event): void
    {
        $this->log($event->actor, $event->channel, 'notification_channel_updated', 'Notification channel updated');
    }

    public function handleNotificationPreferencesUpdated(NotificationPreferencesUpdated $event): void
    {
        activity('notifications')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->withProperties($this->context())
            ->event('notification_preferences_updated')
            ->log('Notification preferences updated');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function log(User $actor, Model $subject, string $event, string $description, array $extra = []): void
    {
        activity('notifications')
            ->causedBy($actor)
            ->performedOn($subject)
            ->withProperties($this->context($extra))
            ->event($event)
            ->log($description);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function context(array $extra = []): array
    {
        return array_merge([
            'ip' => $this->request?->ip(),
            'user_agent' => $this->request?->userAgent(),
        ], $extra);
    }
}
