<?php

namespace App\Domains\Companies\Concerns;

use App\Domains\Companies\Services\CompanyTenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompanyTenant
{
    public static function bootBelongsToCompanyTenant(): void
    {
        static::addGlobalScope('company_tenant', function (Builder $builder): void {
            $tenant = app(CompanyTenant::class);

            if (! $tenant->enforced()) {
                return;
            }

            $model = $builder->getModel();
            $tenant->constrain(
                $builder,
                $model->getTable().'.'.$model->companyTenantColumn(),
                $model->companyTenantSharesUnassigned(),
            );
        });
    }

    public function companyTenantColumn(): string
    {
        return 'company_id';
    }

    public function companyTenantSharesUnassigned(): bool
    {
        return false;
    }
}
