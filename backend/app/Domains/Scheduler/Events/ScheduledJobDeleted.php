<?php

namespace App\Domains\Scheduler\Events;

use App\Domains\Scheduler\Models\ScheduledJob;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduledJobDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ScheduledJob $job,
        public readonly User $actor,
    ) {}
}
