<?php

namespace App\Domains\Analytics\Events;

use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyticsDashboardDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AnalyticsDashboard $dashboard,
        public readonly User $actor,
        public readonly bool $forceDeleted = false
    ) {}
}
