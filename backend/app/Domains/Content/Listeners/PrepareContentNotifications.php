<?php

namespace App\Domains\Content\Listeners;

use App\Domains\Content\Enums\ContentPermission;
use App\Domains\Content\Enums\ContentWorkflowAction;
use App\Domains\Content\Events\ContentCreated;
use App\Domains\Content\Events\ContentDeleted;
use App\Domains\Content\Events\ContentPublished;
use App\Domains\Content\Events\ContentRestored;
use App\Domains\Content\Events\ContentUnpublished;
use App\Domains\Content\Events\ContentUpdated;
use App\Domains\Content\Events\ContentVersionRestored;
use App\Domains\Content\Events\ContentWorkflowTransitioned;
use App\Domains\Content\Notifications\ContentPublishedNotification;
use App\Domains\Content\Notifications\ContentWorkflowNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/** Content notification workflows. */
class PrepareContentNotifications
{
    public function handleContentCreated(ContentCreated $event): void {}

    public function handleContentUpdated(ContentUpdated $event): void {}

    public function handleContentDeleted(ContentDeleted $event): void {}

    public function handleContentRestored(ContentRestored $event): void {}

    public function handleContentPublished(ContentPublished $event): void
    {
        $event->content->loadMissing('creator');

        $recipient = $event->content->creator instanceof User
            ? $event->content->creator
            : null;

        if ($recipient && $recipient->id !== $event->actor->id) {
            $recipient->notify(new ContentPublishedNotification($event->content));
        }
    }

    public function handleContentUnpublished(ContentUnpublished $event): void {}

    public function handleContentVersionRestored(ContentVersionRestored $event): void {}

    public function handleContentWorkflowTransitioned(ContentWorkflowTransitioned $event): void
    {
        $permission = match ($event->history->action) {
            ContentWorkflowAction::Submit->value => ContentPermission::REVIEW,
            ContentWorkflowAction::Review->value => ContentPermission::APPROVE,
            ContentWorkflowAction::Approve->value => ContentPermission::PUBLISH,
            ContentWorkflowAction::Reject->value,
            ContentWorkflowAction::ReturnToDraft->value,
            ContentWorkflowAction::Publish->value,
            ContentWorkflowAction::Archive->value => null,
            default => null,
        };

        $recipients = collect();

        if ($permission) {
            $recipients = User::permission($permission)
                ->where('id', '!=', $event->actor->id)
                ->get();
        }

        $event->content->loadMissing('creator');
        if (
            in_array($event->history->action, [
                ContentWorkflowAction::Reject->value,
                ContentWorkflowAction::Publish->value,
                ContentWorkflowAction::Approve->value,
            ], true)
            && $event->content->creator
            && $event->content->creator->id !== $event->actor->id
        ) {
            $recipients = $recipients->push($event->content->creator)->unique('id');
        }

        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new ContentWorkflowNotification($event->content, $event->history)
            );
        }
    }
}
