<?php

namespace App\Domains\Customers\Events;

use App\Domains\Customers\Models\CustomerAnalyticsSnapshot;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerAnalyticsSnapshotComputed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CustomerAnalyticsSnapshot $snapshot,
        public readonly User $actor
    ) {}
}
