<?php

namespace App\Domains\Analytics\Events;

use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyticsEventRecorded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AnalyticsEvent $analyticsEvent,
        public readonly ?User $actor = null
    ) {}
}
