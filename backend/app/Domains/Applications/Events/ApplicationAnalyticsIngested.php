<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\ApplicationAnalyticsDaily;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationAnalyticsIngested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ApplicationAnalyticsDaily $daily,
        public readonly ?User $actor
    ) {}
}
