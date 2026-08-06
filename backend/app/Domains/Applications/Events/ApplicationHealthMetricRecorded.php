<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\ApplicationHealthMetric;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationHealthMetricRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationHealthMetric $metric,
        public readonly ?User $actor
    ) {}
}
