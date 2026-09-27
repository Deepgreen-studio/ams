<?php

namespace App\Domains\Companies\Services;

use App\Domains\Companies\Models\Company;
use App\Models\User;

class CompanyAccess
{
    /**
     * Null means the user can see every company. An array is the company ids they belong to.
     *
     * @return list<int>|null
     */
    public function accessibleIds(User $user): ?array
    {
        if ($user->hasRole('super-admin')) {
            return null;
        }

        return $user->companies()->pluck('companies.id')->map(fn ($id): int => (int) $id)->all();
    }

    public function canAccess(User $user, Company $company): bool
    {
        $ids = $this->accessibleIds($user);

        return $ids === null || in_array($company->id, $ids, true);
    }

    public function assert(User $user, Company $company): void
    {
        if (! $this->canAccess($user, $company)) {
            abort(404, 'Company not found.');
        }
    }
}
