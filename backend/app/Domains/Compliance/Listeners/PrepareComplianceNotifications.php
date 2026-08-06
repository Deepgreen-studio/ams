<?php

namespace App\Domains\Compliance\Listeners;

use App\Domains\Compliance\Events\ComplianceCaseAssigned;
use App\Domains\Compliance\Events\ComplianceCaseCreated;
use App\Domains\Compliance\Events\ComplianceCaseDeleted;
use App\Domains\Compliance\Events\ComplianceCaseRestored;
use App\Domains\Compliance\Events\ComplianceCaseUpdated;
use App\Domains\Compliance\Notifications\ComplianceCaseAssignedNotification;
use App\Domains\Compliance\Notifications\ComplianceCaseCreatedNotification;
use App\Models\User;

class PrepareComplianceNotifications
{
    public function handleComplianceCaseCreated(ComplianceCaseCreated $event): void
    {
        $recipients = $this->resolveRecipients($event->case->assigned_to, $event->actor->id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new ComplianceCaseCreatedNotification($event->case, $event->actor));
        }
    }

    public function handleComplianceCaseUpdated(ComplianceCaseUpdated $event): void
    {
        // Reserved for future status-change and due-date notifications.
    }

    public function handleComplianceCaseDeleted(ComplianceCaseDeleted $event): void
    {
        // Reserved for future deletion notifications.
    }

    public function handleComplianceCaseRestored(ComplianceCaseRestored $event): void
    {
        // Reserved for future restoration notifications.
    }

    public function handleComplianceCaseAssigned(ComplianceCaseAssigned $event): void
    {
        if ($event->case->assigned_to === null) {
            return;
        }

        /** @var User|null $assignee */
        $assignee = User::query()->find($event->case->assigned_to);

        if (! $assignee || $assignee->id === $event->actor->id) {
            return;
        }

        $assignee->notify(new ComplianceCaseAssignedNotification($event->case, $event->actor));
    }

    /**
     * @return list<User>
     */
    protected function resolveRecipients(?int $assignedTo, int $actorId): array
    {
        if ($assignedTo === null || $assignedTo === $actorId) {
            return [];
        }

        /** @var User|null $assignee */
        $assignee = User::query()->find($assignedTo);

        return $assignee ? [$assignee] : [];
    }
}
