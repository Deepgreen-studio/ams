<?php

namespace App\Domains\Compliance\Listeners;

use App\Domains\Compliance\Events\PrivacyRequestApproved;
use App\Domains\Compliance\Events\PrivacyRequestAssigned;
use App\Domains\Compliance\Events\PrivacyRequestCompleted;
use App\Domains\Compliance\Events\PrivacyRequestCreated;
use App\Domains\Compliance\Events\PrivacyRequestDataDeleted;
use App\Domains\Compliance\Events\PrivacyRequestExportGenerated;
use App\Domains\Compliance\Events\PrivacyRequestIdentityVerified;
use App\Domains\Compliance\Events\PrivacyRequestRejected;
use App\Domains\Compliance\Events\PrivacyRequestStatusChanged;
use App\Domains\Compliance\Events\PrivacyRequestUpdated;
use App\Domains\Compliance\Notifications\PrivacyRequestAssignedNotification;
use App\Domains\Compliance\Notifications\PrivacyRequestCreatedNotification;
use App\Domains\Compliance\Notifications\PrivacyRequestStatusChangedNotification;
use App\Models\User;

class PreparePrivacyRequestNotifications
{
    public function handlePrivacyRequestCreated(PrivacyRequestCreated $event): void
    {
        foreach ($this->assigneeRecipients($event->privacyRequest->assigned_to, $event->actor->id) as $recipient) {
            $recipient->notify(new PrivacyRequestCreatedNotification($event->privacyRequest, $event->actor));
        }
    }

    public function handlePrivacyRequestUpdated(PrivacyRequestUpdated $event): void
    {
        // Reserved for future field-level notification rules.
    }

    public function handlePrivacyRequestAssigned(PrivacyRequestAssigned $event): void
    {
        foreach ($this->assigneeRecipients($event->privacyRequest->assigned_to, $event->actor->id) as $recipient) {
            $recipient->notify(new PrivacyRequestAssignedNotification($event->privacyRequest, $event->actor));
        }
    }

    public function handlePrivacyRequestStatusChanged(PrivacyRequestStatusChanged $event): void
    {
        foreach ($this->assigneeRecipients($event->privacyRequest->assigned_to, $event->actor->id) as $recipient) {
            $recipient->notify(new PrivacyRequestStatusChangedNotification(
                $event->privacyRequest,
                $event->actor,
                $event->previousStatus
            ));
        }
    }

    public function handlePrivacyRequestIdentityVerified(PrivacyRequestIdentityVerified $event): void {}

    public function handlePrivacyRequestApproved(PrivacyRequestApproved $event): void {}

    public function handlePrivacyRequestRejected(PrivacyRequestRejected $event): void {}

    public function handlePrivacyRequestExportGenerated(PrivacyRequestExportGenerated $event): void {}

    public function handlePrivacyRequestDataDeleted(PrivacyRequestDataDeleted $event): void {}

    public function handlePrivacyRequestCompleted(PrivacyRequestCompleted $event): void {}

    /**
     * @return list<User>
     */
    protected function assigneeRecipients(?int $assignedTo, int $actorId): array
    {
        if ($assignedTo === null || $assignedTo === $actorId) {
            return [];
        }

        /** @var User|null $assignee */
        $assignee = User::query()->find($assignedTo);

        return $assignee ? [$assignee] : [];
    }
}
