<?php

namespace App\Domains\Users\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public function __construct(
        public readonly User $user,
        public readonly User $actor,
        public readonly string $context = 'user_updated',
        public readonly array $before = [],
        public readonly array $after = [],
    ) {}
}
