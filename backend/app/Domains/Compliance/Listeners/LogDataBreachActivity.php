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

class LogDataBreachActivity
{
    public function handleDataBreachCreated(DataBreachCreated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties([
                'event' => 'data_breach_created',
                'breach_number' => $event->breach->breach_number,
                'status' => $event->breach->status?->value,
                'severity' => $event->breach->severity?->value,
            ])
            ->log('Data breach reported');
    }

    public function handleDataBreachUpdated(DataBreachUpdated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties([
                'event' => 'data_breach_updated',
                'breach_number' => $event->breach->breach_number,
            ])
            ->log('Data breach updated');
    }

    public function handleDataBreachAssigned(DataBreachAssigned $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties([
                'event' => 'data_breach_assigned',
                'assigned_to' => $event->breach->assigned_to,
            ])
            ->log('Data breach assigned');
    }

    public function handleDataBreachStatusChanged(DataBreachStatusChanged $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties([
                'event' => 'data_breach_status_changed',
                'from' => $event->previousStatus,
                'to' => $event->breach->status?->value,
            ])
            ->log('Data breach status changed');
    }

    public function handleDataBreachRiskAssessed(DataBreachRiskAssessed $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties([
                'event' => 'data_breach_risk_assessed',
                'risk_score' => $event->breach->risk_score,
                'risk_level' => $event->breach->risk_level?->value,
            ])
            ->log('Data breach risk assessed');
    }

    public function handleDataBreachContained(DataBreachContained $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties(['event' => 'data_breach_contained'])
            ->log('Data breach contained');
    }

    public function handleDataBreachRecovered(DataBreachRecovered $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties(['event' => 'data_breach_recovered'])
            ->log('Data breach recovery recorded');
    }

    public function handleDataBreachClosed(DataBreachClosed $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties(['event' => 'data_breach_closed'])
            ->log('Data breach closed');
    }

    public function handleDataBreachDeleted(DataBreachDeleted $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties(['event' => 'data_breach_deleted'])
            ->log('Data breach deleted');
    }

    public function handleDataBreachRestored(DataBreachRestored $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties(['event' => 'data_breach_restored'])
            ->log('Data breach restored');
    }

    public function handleDataBreachActionRecorded(DataBreachActionRecorded $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties([
                'event' => 'data_breach_action_recorded',
                'action_uuid' => $event->action->uuid,
                'action_type' => $event->action->action_type?->value,
            ])
            ->log('Data breach action recorded');
    }

    public function handleDataBreachNotificationSent(DataBreachNotificationSent $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->breach)
            ->withProperties([
                'event' => 'data_breach_notification_sent',
                'notification_uuid' => $event->notification->uuid,
                'notification_type' => $event->notification->notification_type?->value,
                'recipient' => $event->notification->recipient,
            ])
            ->log('Data breach notification sent');
    }
}
