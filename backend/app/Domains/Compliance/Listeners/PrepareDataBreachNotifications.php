<?php

namespace App\Domains\Compliance\Listeners;

use App\Domains\Compliance\Events\DataBreachActionRecorded;
use App\Domains\Compliance\Events\DataBreachAssigned;
use App\Domains\Compliance\Events\DataBreachClosed;
use App\Domains\Compliance\Events\DataBreachContained;
use App\Domains\Compliance\Events\DataBreachCreated;
use App\Domains\Compliance\Events\DataBreachDeleted;
use App\Domains\Compliance\Events\DataBreachNotificationSent;
use App\Domains\Compliance\Events\DataBreachRecovered;
use App\Domains\Compliance\Events\DataBreachRestored;
use App\Domains\Compliance\Events\DataBreachRiskAssessed;
use App\Domains\Compliance\Events\DataBreachStatusChanged;
use App\Domains\Compliance\Events\DataBreachUpdated;
use App\Domains\Compliance\Models\DataBreach;
use App\Domains\Compliance\Notifications\DataBreachAssignedNotification;
use App\Domains\Compliance\Notifications\DataBreachStatusChangedNotification;
use App\Models\User;

class PrepareDataBreachNotifications
{
    public function handleDataBreachCreated(DataBreachCreated $event): void
    {
        $this->notifyAssignee($event->breach, $event->actor, assigned: true);
    }

    public function handleDataBreachUpdated(DataBreachUpdated $event): void
    {
        // No-op placeholder for future digest rules.
    }

    public function handleDataBreachAssigned(DataBreachAssigned $event): void
    {
        $this->notifyAssignee($event->breach, $event->actor, assigned: true);
    }

    public function handleDataBreachStatusChanged(DataBreachStatusChanged $event): void
    {
        if (! $event->breach->assignee) {
            return;
        }

        if ($event->breach->assignee->id === $event->actor->id) {
            return;
        }

        $event->breach->assignee->notify(
            new DataBreachStatusChangedNotification($event->breach, $event->actor, $event->previousStatus)
        );
    }

    public function handleDataBreachRiskAssessed(DataBreachRiskAssessed $event): void
    {
        $this->notifyAssignee($event->breach, $event->actor);
    }

    public function handleDataBreachContained(DataBreachContained $event): void
    {
        $this->notifyAssignee($event->breach, $event->actor);
    }

    public function handleDataBreachRecovered(DataBreachRecovered $event): void
    {
        $this->notifyAssignee($event->breach, $event->actor);
    }

    public function handleDataBreachClosed(DataBreachClosed $event): void
    {
        $this->notifyAssignee($event->breach, $event->actor);
    }

    public function handleDataBreachDeleted(DataBreachDeleted $event): void
    {
        // Soft-delete notifications intentionally omitted.
    }

    public function handleDataBreachRestored(DataBreachRestored $event): void
    {
        $this->notifyAssignee($event->breach, $event->actor);
    }

    public function handleDataBreachActionRecorded(DataBreachActionRecorded $event): void
    {
        // Operational noise; skip assignee mail.
    }

    public function handleDataBreachNotificationSent(DataBreachNotificationSent $event): void
    {
        $this->notifyAssignee($event->breach, $event->actor);
    }

    private function notifyAssignee(DataBreach $breach, User $actor, bool $assigned = false): void
    {
        $assignee = $breach->assignee;
        if (! $assignee || $assignee->id === $actor->id) {
            return;
        }

        if ($assigned) {
            $assignee->notify(new DataBreachAssignedNotification($breach, $actor));

            return;
        }

        $assignee->notify(new DataBreachStatusChangedNotification(
            $breach,
            $actor,
            $breach->status?->value
        ));
    }
}
