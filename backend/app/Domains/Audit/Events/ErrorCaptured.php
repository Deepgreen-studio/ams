<?php

namespace App\Domains\Audit\Events;

use App\Domains\Audit\Models\ErrorLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ErrorCaptured
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly ErrorLog $errorLog) {}
}
