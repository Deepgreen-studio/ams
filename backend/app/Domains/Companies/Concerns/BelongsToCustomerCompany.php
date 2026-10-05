<?php

namespace App\Domains\Companies\Concerns;

use App\Domains\Companies\Services\CompanyAccess;
use App\Domains\Companies\Services\CompanyTenant;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToCustomerCompany
{
    public static function bootBelongsToCustomerCompany(): void
    {
        static::addGlobalScope('customer_company', function (Builder $builder): void {
            $tenant = app(CompanyTenant::class);

            if (! $tenant->enforced()) {
                return;
            }

            $model = $builder->getModel();
            $table = $model->getTable();
            $column = $model->customerCompanyColumn();

            $builder->whereIn(
                $table.'.'.$column,
                Customer::query()->select('customers.id'),
            );
        });

        static::creating(function (Model $model): void {
            $tenant = app(CompanyTenant::class);

            if (! $tenant->enforced()) {
                return;
            }

            $user = Auth::user();

            if (! $user instanceof User) {
                return;
            }

            $customerId = $model->getAttribute($model->customerCompanyColumn());

            if (! app(CompanyAccess::class)->allowsCustomer($user, $customerId)) {
                throw new AuthorizationException('You cannot create records for another company.');
            }
        });
    }

    public function customerCompanyColumn(): string
    {
        return 'customer_id';
    }
}
