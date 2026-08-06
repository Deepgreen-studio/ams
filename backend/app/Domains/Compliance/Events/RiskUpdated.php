<?php

namespace App\Domains\Compliance\Events;

use App\Domains\Compliance\Models\RiskRegister;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiskUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly RiskRegister $risk,
        public readonly User $actor
    ) {}
}