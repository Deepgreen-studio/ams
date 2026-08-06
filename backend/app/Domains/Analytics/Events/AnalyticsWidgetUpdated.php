<?php

namespace App\Domains\Analytics\Events;

use App\Domains\Analytics\Models\AnalyticsWidget;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyticsWidgetUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AnalyticsWidget $widget,
        public readonly User $actor
    ) {}
}
