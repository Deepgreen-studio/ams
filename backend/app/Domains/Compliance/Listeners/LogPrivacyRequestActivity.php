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

class LogPrivacyRequestActivity
{
    public function handlePrivacyRequestCreated(PrivacyRequestCreated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->privacyRequest)
            ->withProperties([
                'event' => 'privacy_request_created',
                'request_number' => $event->privacyRequest->request_number,
                'request_type' => $event->privacyRequest->request_type?->value,
                'status' => $event->privacyRequest->status?->value,
            ])
            ->log('Privacy request created');
    }

    public function handlePrivacyRequestUpdated(PrivacyRequestUpdated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->privacyRequest)
            ->withProperties([
                'event' => 'privacy_request_updated',
                'request_number' => $event->privacyRequest->request_number,
                'status' => $event->privacyRequest->status?->value,
            ])
            ->log('Privacy request updated');
    }

    public function handlePrivacyRequestAssigned(PrivacyRequestAssigned $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->privacyRequest)
            ->withProperties([
                'event' => 'privacy_request_assigned',
                'request_number' => $event->privacyRequest->request_number,
                'assigned_to' => $event->privacyRequest->assigned_to,
            ])
            ->log('Privacy request assigned');
    }

    public function handlePrivacyRequestStatusChanged(PrivacyRequestStatusChanged $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->privacyRequest)
            ->withProperties([
                'event' => 'privacy_request_status_changed',
                'request_number' => $event->privacyRequest->request_number,
                'from_status' => $event->previousStatus,
                'to_status' => $event->privacyRequest->status?->value,
            ])
            ->log('Privacy request status changed');
    }

    public function handlePrivacyRequestIdentityVerified(PrivacyRequestIdentityVerified $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->privacyRequest)
            ->withProperties([
                'event' => 'privacy_request_identity_verified',
                'request_number' => $event->privacyRequest->request_number,
                'verified' => $event->verified,
            ])
            ->log($event->verified ? 'Privacy request identity verified' : 'Privacy request identity failed');
    }

    public function handlePrivacyRequestApproved(PrivacyRequestApproved $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->privacyRequest)
            ->withProperties([
                'event' => 'privacy_request_approved',
                'request_number' => $event->privacyRequest->request_number,
                'decision' => $event->privacyRequest->decision?->value,
            ])
            ->log('Privacy request approved');
    }

    public function handlePrivacyRequestRejected(PrivacyRequestRejected $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->privacyRequest)
            ->withProperties([
                'event' => 'privacy_request_rejected',
                'request_number' => $event->privacyRequest->request_number,
            ])
            ->log('Privacy request rejected');
    }

    public function handlePrivacyRequestExportGenerated(PrivacyRequestExportGenerated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->privacyRequest)
            ->withProperties([
                'event' => 'privacy_request_export_generated',
                'request_number' => $event->privacyRequest->request_number,
                'export_file_path' => $event->privacyRequest->export_file_path,
            ])
            ->log('Privacy request export generated');
    }

    public function handlePrivacyRequestDataDeleted(PrivacyRequestDataDeleted $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->privacyRequest)
            ->withProperties([
                'event' => 'privacy_request_data_deleted',
                'request_number' => $event->privacyRequest->request_number,
            ])
            ->log('Privacy request data deletion confirmed');
    }

    public function handlePrivacyRequestCompleted(PrivacyRequestCompleted $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->privacyRequest)
            ->withProperties([
                'event' => 'privacy_request_completed',
                'request_number' => $event->privacyRequest->request_number,
            ])
            ->log('Privacy request completed');
    }
}
