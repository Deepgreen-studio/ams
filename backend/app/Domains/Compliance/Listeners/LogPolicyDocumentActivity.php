<?php

namespace App\Domains\Compliance\Listeners;

use App\Domains\Compliance\Events\PolicyApproved;
use App\Domains\Compliance\Events\PolicyCreated;
use App\Domains\Compliance\Events\PolicyPublished;
use App\Domains\Compliance\Events\PolicyRejected;
use App\Domains\Compliance\Events\PolicySubmittedForReview;
use App\Domains\Compliance\Events\PolicyUpdated;
use App\Domains\Compliance\Events\PolicyVersionRestored;

class LogPolicyDocumentActivity
{
    public function handlePolicyCreated(PolicyCreated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->policy)
            ->withProperties([
                'event' => 'policy_created',
                'policy_number' => $event->policy->policy_number,
                'version' => $event->policy->current_version,
            ])
            ->log('Policy document created');
    }

    public function handlePolicyUpdated(PolicyUpdated $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->policy)
            ->withProperties([
                'event' => 'policy_updated',
                'version' => $event->policy->current_version,
                'status' => $event->policy->status?->value,
            ])
            ->log('Policy document updated');
    }

    public function handlePolicySubmittedForReview(PolicySubmittedForReview $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->policy)
            ->withProperties(['event' => 'policy_submitted_for_review'])
            ->log('Policy submitted for review');
    }

    public function handlePolicyApproved(PolicyApproved $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->policy)
            ->withProperties(['event' => 'policy_approved'])
            ->log('Policy approved');
    }

    public function handlePolicyRejected(PolicyRejected $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->policy)
            ->withProperties(['event' => 'policy_rejected'])
            ->log('Policy rejected');
    }

    public function handlePolicyPublished(PolicyPublished $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->policy)
            ->withProperties(['event' => 'policy_published'])
            ->log('Policy published');
    }

    public function handlePolicyVersionRestored(PolicyVersionRestored $event): void
    {
        activity('compliance')
            ->causedBy($event->actor)
            ->performedOn($event->policy)
            ->withProperties([
                'event' => 'policy_version_restored',
                'restored_from' => $event->restoredFromVersion,
                'new_version' => $event->policy->current_version,
            ])
            ->log('Policy version restored');
    }
}
