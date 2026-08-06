<?php

namespace App\Domains\Settings\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConfigurationChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  list<string>  $keys
     */
    public function __construct(
        public readonly string $group,
        public readonly array $keys,
        public readonly User $actor
    ) {}
}
