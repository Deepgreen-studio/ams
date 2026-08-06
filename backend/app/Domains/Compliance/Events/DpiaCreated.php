<?php

namespace App\Domains\Compliance\Events;

use App\Domains\Compliance\Models\DpiaAssessment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DpiaCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DpiaAssessment $assessment,
        public readonly User $actor
    ) {}
}