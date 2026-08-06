<?php

namespace App\Domains\Analytics\Events;

use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyticsDashboardUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AnalyticsDashboard $dashboard,
        public readonly User $actor
    ) {}
}
