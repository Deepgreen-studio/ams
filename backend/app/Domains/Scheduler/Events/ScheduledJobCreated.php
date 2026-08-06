<?php

namespace App\Domains\Scheduler\Events;

use App\Domains\Scheduler\Models\ScheduledJob;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduledJobCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ScheduledJob $job,
        public readonly User $actor,
    ) {}
}
