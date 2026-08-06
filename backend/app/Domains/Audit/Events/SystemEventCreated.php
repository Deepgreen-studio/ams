<?php

namespace App\Domains\Audit\Events;

use App\Domains\Audit\Models\SystemEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemEventCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly SystemEvent $systemEvent) {}
}
