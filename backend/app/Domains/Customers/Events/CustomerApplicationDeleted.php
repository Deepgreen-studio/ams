<?php

namespace App\Domains\Customers\Events;

use App\Domains\Customers\Models\CustomerApplication;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerApplicationDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CustomerApplication $assignment,
        public readonly User $actor
    ) {}
}
