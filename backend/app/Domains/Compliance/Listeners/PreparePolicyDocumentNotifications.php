<?php

namespace App\Domains\Compliance\Listeners;

use App\Domains\Compliance\Events\PolicyApproved;
use App\Domains\Compliance\Events\PolicyCreated;
use App\Domains\Compliance\Events\PolicyPublished;
use App\Domains\Compliance\Events\PolicyRejected;
use App\Domains\Compliance\Events\PolicySubmittedForReview;
use App\Domains\Compliance\Events\PolicyUpdated;
use App\Domains\Compliance\Events\PolicyVersionRestored;
use App\Domains\Compliance\Models\PolicyDocument;
use App\Domains\Compliance\Notifications\PolicyDocumentStatusNotification;
use App\Models\User;

class PreparePolicyDocumentNotifications
{
    public function handlePolicyCreated(PolicyCreated $event): void
    {
        $this->notifyAssignee($event->policy, $event->actor, 'created');
    }

    public function handlePolicyUpdated(PolicyUpdated $event): void
    {
        // Avoid noisy update emails.
    }

    public function handlePolicySubmittedForReview(PolicySubmittedForReview $event): void
    {
        $this->notifyAssignee($event->policy, $event->actor, 'submitted');
    }

    public function handlePolicyApproved(PolicyApproved $event): void
    {
        $this->notifyAssignee($event->policy, $event->actor, 'approved');
    }

    public function handlePolicyRejected(PolicyRejected $event): void
    {
        $this->notifyAssignee($event->policy, $event->actor, 'rejected');
    }

    public function handlePolicyPublished(PolicyPublished $event): void
    {
        $this->notifyAssignee($event->policy, $event->actor, 'published');
    }

    public function handlePolicyVersionRestored(PolicyVersionRestored $event): void
    {
        $this->notifyAssignee($event->policy, $event->actor, 'restored');
    }

    private function notifyAssignee(PolicyDocument $policy, User $actor, string $eventType): void
    {
        $assignee = $policy->assignee;
        if (! $assignee || $assignee->id === $actor->id) {
            return;
        }

        $assignee->notify(new PolicyDocumentStatusNotification($policy, $actor, $eventType));
    }
}
