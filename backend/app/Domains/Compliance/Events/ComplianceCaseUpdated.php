<?php

namespace App\Domains\Compliance\Events;

use App\Domains\Compliance\Models\ComplianceCase;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplianceCaseUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ComplianceCase $case,
        public readonly User $actor
    ) {}
}
