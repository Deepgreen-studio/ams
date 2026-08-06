<?php

namespace App\Domains\Compliance\Events;

use App\Domains\Compliance\Models\BreachAction;
use App\Domains\Compliance\Models\DataBreach;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataBreachActionRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DataBreach $breach,
        public readonly BreachAction $action,
        public readonly User $actor
    ) {}
}
