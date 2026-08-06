<?php

namespace App\Domains\Applications\Events;

use App\Domains\Applications\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationRestored
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Application $application,
        public readonly User $actor
    ) {}
}
