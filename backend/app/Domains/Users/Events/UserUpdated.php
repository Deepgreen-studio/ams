<?php

namespace App\Domains\Users\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly User $actor,
        public readonly string $context = 'user_updated'
    ) {}
}
