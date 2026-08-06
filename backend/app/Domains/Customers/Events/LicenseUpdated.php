<?php

namespace App\Domains\Customers\Events;

use App\Domains\Customers\Models\License;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicenseUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly License $license,
        public readonly User $actor
    ) {}
}
