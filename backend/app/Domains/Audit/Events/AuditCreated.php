<?php

namespace App\Domains\Audit\Events;

use App\Domains\Audit\Models\AuditLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly AuditLog $audit) {}
}
