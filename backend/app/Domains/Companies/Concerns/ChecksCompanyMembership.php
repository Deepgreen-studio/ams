<?php

namespace App\Domains\Companies\Concerns;

use App\Domains\Companies\Services\CompanyAccess;
use App\Models\User;

trait ChecksCompanyMembership
{
    protected function inCompany(User $user, object $model): bool
    {
        return app(CompanyAccess::class)->allowsModel($user, $model);
    }
}
