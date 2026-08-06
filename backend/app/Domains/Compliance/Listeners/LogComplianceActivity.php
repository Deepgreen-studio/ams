<?php

namespace App\Domains\Compliance\Listeners;

use App\Domains\Compliance\Events\ComplianceCaseAssigned;
use App\Domains\Compliance\Events\ComplianceCaseCreated;
use App\Domains\Compliance\Events\ComplianceCaseDeleted;
use App\Domains\Compliance\Events\ComplianceCaseRestored;
use App\Domains\Compliance\Events\ComplianceCaseUpdated;

class LogComplianceActivity
{
    public function handleComplianceCaseCreated(ComplianceCaseCreated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->case)
            ->withProperties([
                'event' => 'compliance_case_created',
                'case_number' => $event->case->case_number,
                'title' => $event->case->title,
                'case_type' => $event->case->case_type?->value ?? $event->case->case_type,
                'priority' => $event->case->priority?->value ?? $event->case->priority,
                'status' => $event->case->status?->value ?? $event->case->status,
            ])
            ->log('Compliance case created');
    }

    public function handleComplianceCaseUpdated(ComplianceCaseUpdated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->case)
            ->withProperties([
                'event' => 'compliance_case_updated',
                'case_number' => $event->case->case_number,
                'title' => $event->case->title,
                'status' => $event->case->status?->value ?? $event->case->status,
                'priority' => $event->case->priority?->value ?? $event->case->priority,
            ])
            ->log('Compliance case updated');
    }

    public function handleComplianceCaseDeleted(ComplianceCaseDeleted $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->case)
            ->withProperties([
                'event' => 'compliance_case_deleted',
                'case_number' => $event->case->case_number,
                'title' => $event->case->title,
            ])
            ->log('Compliance case deleted');
    }

    public function handleComplianceCaseRestored(ComplianceCaseRestored $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->case)
            ->withProperties([
                'event' => 'compliance_case_restored',
                'case_number' => $event->case->case_number,
                'title' => $event->case->title,
            ])
            ->log('Compliance case restored');
    }

    public function handleComplianceCaseAssigned(ComplianceCaseAssigned $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->case)
            ->withProperties([
                'event' => 'compliance_case_assigned',
                'case_number' => $event->case->case_number,
                'assigned_to' => $event->case->assigned_to,
            ])
            ->log('Compliance case assigned');
    }
}
