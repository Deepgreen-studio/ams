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

class LogDpiaActivity
{
    public function handleDpiaCreated(DpiaCreated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->assessment)
            ->withProperties([
                'event' => 'dpia_created',
                'assessment_number' => $event->assessment->assessment_number,
                'template' => $event->assessment->template_code?->value,
            ])
            ->log('DPIA assessment created');
    }

    public function handleDpiaUpdated(DpiaUpdated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->assessment)
            ->withProperties([
                'event' => 'dpia_updated',
                'status' => $event->assessment->status?->value,
            ])
            ->log('DPIA assessment updated');
    }

    public function handleDpiaSubmitted(DpiaSubmitted $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->assessment)
            ->withProperties(['event' => 'dpia_submitted'])
            ->log('DPIA submitted for review');
    }

    public function handleDpiaApproved(DpiaApproved $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->assessment)
            ->withProperties(['event' => 'dpia_approved'])
            ->log('DPIA approved');
    }

    public function handleDpiaRejected(DpiaRejected $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->assessment)
            ->withProperties(['event' => 'dpia_rejected'])
            ->log('DPIA rejected');
    }

    public function handleRiskCreated(RiskCreated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->risk)
            ->withProperties([
                'event' => 'risk_created',
                'risk_number' => $event->risk->risk_number,
                'risk_score' => $event->risk->risk_score,
            ])
            ->log('Risk registered');
    }

    public function handleRiskUpdated(RiskUpdated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->risk)
            ->withProperties([
                'event' => 'risk_updated',
                'status' => $event->risk->status?->value,
                'risk_score' => $event->risk->risk_score,
            ])
            ->log('Risk updated');
    }

    public function handleRiskActionRecorded(RiskActionRecorded $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->risk)
            ->withProperties([
                'event' => 'risk_action_recorded',
                'action_uuid' => $event->action->uuid,
                'action_type' => $event->action->action_type?->value,
            ])
            ->log('Risk action recorded');
    }
}
