<?php

namespace App\Domains\Compliance\Events;

use App\Domains\Compliance\Models\PolicyDocument;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PolicySubmittedForReview
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PolicyDocument $policy,
        public readonly User $actor
    ) {}
}