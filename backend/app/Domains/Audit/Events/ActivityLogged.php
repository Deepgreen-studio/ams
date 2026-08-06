<?php

namespace App\Domains\Audit\Events;

use App\Domains\Audit\Models\ActivityLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityLogged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly ActivityLog $activity) {}
}
