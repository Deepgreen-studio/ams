<?php

namespace App\Domains\Scheduler\Listeners;

use App\Domains\Scheduler\Events\ScheduledJobCreated;
use App\Domains\Scheduler\Events\ScheduledJobDeleted;
use App\Domains\Scheduler\Events\ScheduledJobUpdated;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LogSchedulerActivity
{
    public function __construct(
        private readonly ?Request $request = null,
    ) {}

    public function handleScheduledJobCreated(ScheduledJobCreated $event): void
    {
        $this->log($event->actor, $event->job, 'scheduled_job_created', 'Scheduled job created');
    }

    public function handleScheduledJobUpdated(ScheduledJobUpdated $event): void
    {
        $this->log($event->actor, $event->job, 'scheduled_job_updated', 'Scheduled job updated');
    }

    public function handleScheduledJobDeleted(ScheduledJobDeleted $event): void
    {
        $this->log($event->actor, $event->job, 'scheduled_job_deleted', 'Scheduled job deleted');
    }

    private function log(User $actor, Model $subject, string $event, string $description): void
    {
        activity('scheduler')
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
