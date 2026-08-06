<?php

namespace App\Domains\Companies\Events;

use App\Domains\Companies\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Company $company, public readonly User $actor) {}
}
