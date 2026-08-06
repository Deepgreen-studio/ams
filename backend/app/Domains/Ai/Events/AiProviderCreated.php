<?php

namespace App\Domains\Ai\Events;

use App\Domains\Ai\Models\AiProvider;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiProviderCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AiProvider $provider,
        public readonly User $actor,
    ) {}
}
