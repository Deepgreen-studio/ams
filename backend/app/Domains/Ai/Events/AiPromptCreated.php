<?php

namespace App\Domains\Ai\Events;

use App\Domains\Ai\Models\AiPrompt;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiPromptCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly AiPrompt $prompt,
        public readonly User $actor,
    ) {}
}
