<?php

namespace App\Domains\Workflows\Events;

use App\Domains\Workflows\Models\Workflow;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Workflow $workflow,
        public readonly User $actor,
    ) {}
}
