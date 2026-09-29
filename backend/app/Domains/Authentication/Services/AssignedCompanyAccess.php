<?php

namespace App\Domains\Authentication\Services;

use App\Domains\Companies\Enums\CompanyStatus;
use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Models\User;

class AssignedCompanyAccess
{
    public const BLOCK_CODE = 'COMPANY_INACTIVE';

    /**
     * Message when the company assigned to this login is not active.
     * Super admins and users with no company assignment are allowed through.
     */
    public function blockMessage(User $user): ?string
    {
        if ($user->hasRole('super-admin')) {
            return null;
        }

        $company = $this->assignedCompany($user);

        if (! $company instanceof Company) {
            return null;
        }

        $name = trim((string) $company->company_name);
        $label = $name !== '' ? $name : 'Your company';

        if ($company->trashed()) {
            return "Your company {$label} is no longer available. You cannot sign in.";
        }

        $status = $company->status instanceof CompanyStatus
            ? $company->status
            : CompanyStatus::tryFrom((string) $company->status);

        if ($status === CompanyStatus::Active) {
            return null;
        }

        $reason = match ($status) {
            CompanyStatus::Suspended => 'suspended',
            CompanyStatus::Pending => 'pending activation',
            CompanyStatus::Inactive => 'inactive',
            default => 'not active',
        };

        return "Your company {$label} is {$reason}. You cannot sign in.";
    }

    /**
     * Primary company membership, otherwise the customer's company.
     * Tenant scope is bypassed because login runs before a user is authenticated.
     */
    public function assignedCompany(User $user): ?Company
    {
        $company = $user->companies()
            ->withoutGlobalScope('company_tenant')
            ->withTrashed()
            ->orderByDesc('company_user.is_primary')
            ->orderBy('company_user.id')
            ->first();

        if ($company instanceof Company) {
            return $company;
        }

        if ($user->customer_id === null) {
            return null;
        }

        $customer = Customer::query()
            ->withoutGlobalScope('company_tenant')
            ->with(['company' => function ($query): void {
                $query->withoutGlobalScope('company_tenant')->withTrashed();
            }])
            ->find($user->customer_id);

        return $customer?->company;
    }
}
