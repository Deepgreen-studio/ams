<?php

namespace App\Domains\Compliance\Listeners;

use App\Domains\Compliance\Events\DpiaApproved;
use App\Domains\Compliance\Events\DpiaCreated;
use App\Domains\Compliance\Events\DpiaRejected;
use App\Domains\Compliance\Events\DpiaSubmitted;
use App\Domains\Compliance\Events\DpiaUpdated;
use App\Domains\Compliance\Events\RiskActionRecorded;
use App\Domains\Compliance\Events\RiskCreated;
use App\Domains\Compliance\Events\RiskUpdated;
use App\Domains\Compliance\Models\DpiaAssessment;
use App\Domains\Compliance\Notifications\DpiaStatusNotification;
use App\Models\User;

class PrepareDpiaNotifications
{
    public function handleDpiaCreated(DpiaCreated $event): void
    {
        $this->notifyAssignee($event->assessment, $event->actor, 'created');
    }

    public function handleDpiaUpdated(DpiaUpdated $event): void
    {
        // Avoid noisy update emails.
    }

    public function handleDpiaSubmitted(DpiaSubmitted $event): void
    {
        $this->notifyAssignee($event->assessment, $event->actor, 'submitted');
    }

    public function handleDpiaApproved(DpiaApproved $event): void
    {
        $this->notifyAssignee($event->assessment, $event->actor, 'approved');
    }

    public function handleDpiaRejected(DpiaRejected $event): void
    {
        $this->notifyAssignee($event->assessment, $event->actor, 'rejected');
    }

    public function handleRiskCreated(RiskCreated $event): void
    {
        // Owner notifications can be added later.
    }

    public function handleRiskUpdated(RiskUpdated $event): void
    {
        // No-op for now.
    }

    public function handleRiskActionRecorded(RiskActionRecorded $event): void
    {
        // Operational noise; skip.
    }

    private function notifyAssignee(DpiaAssessment $assessment, User $actor, string $eventType): void
    {
        $assignee = $assessment->assignee;
        if (! $assignee || $assignee->id === $actor->id) {
            return;
        }

        $assignee->notify(new DpiaStatusNotification($assessment, $actor, $eventType));
    }
}
