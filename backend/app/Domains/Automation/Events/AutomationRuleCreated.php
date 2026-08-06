<?php

namespace App\Domains\Automation\Events;

use App\Domains\Automation\Models\AutomationRule;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AutomationRuleCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AutomationRule $rule,
        public readonly User $actor,
    ) {}
}
