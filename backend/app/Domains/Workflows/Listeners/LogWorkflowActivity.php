<?php

namespace App\Domains\Workflows\Listeners;

use App\Domains\Workflows\Events\WorkflowCreated;
use App\Domains\Workflows\Events\WorkflowDeleted;
use App\Domains\Workflows\Events\WorkflowUpdated;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LogWorkflowActivity
{
    public function __construct(
        private readonly ?Request $request = null,
    ) {}

    public function handleWorkflowCreated(WorkflowCreated $event): void
    {
        $this->log($event->actor, $event->workflow, 'workflow_created', 'Workflow definition created');
    }

    public function handleWorkflowUpdated(WorkflowUpdated $event): void
    {
        $this->log($event->actor, $event->workflow, 'workflow_updated', 'Workflow definition updated');
    }

    public function handleWorkflowDeleted(WorkflowDeleted $event): void
    {
        $this->log($event->actor, $event->workflow, 'workflow_deleted', 'Workflow definition deleted');
    }

    private function log(User $actor, Model $subject, string $event, string $description): void
    {
        activity('workflows')
            ->causedBy($actor)
            ->performedOn($subject)
            ->withProperties([
                'ip' => $this->request?->ip(),
                'user_agent' => $this->request?->userAgent(),
            ])
            ->event($event)
            ->log($description);
    }
}
