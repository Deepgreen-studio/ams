<?php

namespace App\Domains\Customers\Events;

use App\Domains\Customers\Models\CustomerTask;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerTaskRestored
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CustomerTask $task,
        public readonly User $actor
    ) {}
}
