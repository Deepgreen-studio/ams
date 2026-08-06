<?php

namespace App\Domains\Customers\Events;

use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerRestored
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Customer $customer,
        public readonly User $actor
    ) {}
}
