<?php

namespace App\Domains\Companies\Services;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public function allowsCompany(User $user, mixed $companyId): bool
    {
        if ($companyId === null || $companyId === '') {
            return true;
        }

        $ids = app(CompanyTenant::class)->idsFor($user);

        return $ids === null || in_array((int) $companyId, $ids, true);
    }

    public function allowsCustomer(User $user, mixed $customerId): bool
    {
        if ($customerId === null || $customerId === '') {
            return false;
        }

        $companyId = Customer::query()->whereKey($customerId)->value('company_id');

        return $companyId !== null && $this->allowsCompany($user, $companyId);
    }

    public function allowsModel(User $user, object $model): bool
    {
        if ($model instanceof Company) {
            return $this->allowsCompany($user, $model->getKey());
        }

        if ($model instanceof Model) {
            $attributes = $model->getAttributes();

            if (array_key_exists('company_id', $attributes)) {
                return $this->allowsCompany($user, $attributes['company_id']);
            }

            if (array_key_exists('customer_id', $attributes)) {
                return $this->allowsCustomer($user, $attributes['customer_id']);
            }
        }

        return true;
    }

    public function sharesCompany(User $actor, User $subject): bool
    {
        if ($actor->id === $subject->id) {
            return true;
        }

        $ids = app(CompanyTenant::class)->idsFor($actor);

        if ($ids === null) {
            return true;
        }

        if ($ids === []) {
            return false;
        }

        return DB::table('company_user')
            ->where('user_id', $subject->id)
            ->whereIn('company_id', $ids)
            ->exists();
    }
}
