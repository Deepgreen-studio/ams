<?php

namespace App\Domains\Companies\Services;

use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Enums\IntegrationStatus;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyStatusCascade
{
    /**
     * @return list<string>
     */
    public function holdingStatuses(): array
    {
        return ['inactive', 'suspended'];
    }

    /**
     * @return array<string, int>
     */
    public function hold(Company $company, string $status, User $actor): array
    {
        $counts = [
            'applications' => $this->holdApplications($company),
            'integrations' => $this->holdIntegrations($company),
            'users' => $this->holdUsers($company, $status),
            'support' => $this->holdSupport($company),
        ];

        activity('companies')
            ->causedBy($actor)
            ->performedOn($company)
            ->event('status_cascade')
            ->withProperties([
                'event' => 'status_cascade',
                'name' => $company->company_name,
                'status' => $status,
                'counts' => $counts,
            ])
            ->log("Company {$status} applied to users, applications, integrations, and support");

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    public function release(Company $company, User $actor): array
    {
        $counts = [
            'applications' => $this->releaseTable('applications', $company->id),
            'integrations' => $this->releaseTable('integrations', $company->id),
            'users' => $this->releaseUsers($company),
            'support' => $this->releaseTable('support_tickets', $company->id),
        ];

        activity('companies')
            ->causedBy($actor)
            ->performedOn($company)
            ->event('status_cascade')
            ->withProperties([
                'event' => 'status_cascade_released',
                'name' => $company->company_name,
                'counts' => $counts,
            ])
            ->log('Company activation restored held users, applications, integrations, and support');

        return $counts;
    }

    private function holdApplications(Company $company): int
    {
        return Application::query()
            ->where('company_id', $company->id)
            ->where('status', ApplicationStatus::Active->value)
            ->where('company_status_hold', false)
            ->update([
                'held_status' => ApplicationStatus::Active->value,
                'status' => ApplicationStatus::Inactive->value,
                'company_status_hold' => true,
                'updated_at' => now(),
            ]);
    }

    private function holdIntegrations(Company $company): int
    {
        return Integration::query()
            ->where('company_id', $company->id)
            ->where('status', IntegrationStatus::Active->value)
            ->where('company_status_hold', false)
            ->update([
                'held_status' => IntegrationStatus::Active->value,
                'status' => IntegrationStatus::Inactive->value,
                'company_status_hold' => true,
                'updated_at' => now(),
            ]);
    }

    private function holdUsers(Company $company, string $status): int
    {
        return DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->where('company_status_hold', false)
            ->update([
                'held_status' => 'active',
                'status' => $status,
                'company_status_hold' => true,
                'updated_at' => now(),
            ]);
    }

    private function holdSupport(Company $company): int
    {
        $open = [
            SupportTicketStatus::Open->value,
            SupportTicketStatus::InProgress->value,
            SupportTicketStatus::WaitingForCustomer->value,
            SupportTicketStatus::Reopened->value,
        ];

        $updated = 0;
        foreach ($open as $status) {
            $updated += SupportTicket::query()
                ->where('company_id', $company->id)
                ->where('status', $status)
                ->where('company_status_hold', false)
                ->update([
                    'held_status' => $status,
                    'status' => SupportTicketStatus::Pending->value,
                    'company_status_hold' => true,
                    'updated_at' => now(),
                ]);
        }

        return $updated;
    }

    private function releaseTable(string $table, int $companyId): int
    {
        return DB::table($table)
            ->where('company_id', $companyId)
            ->where('company_status_hold', true)
            ->whereNotNull('held_status')
            ->update([
                'status' => DB::raw('held_status'),
                'held_status' => null,
                'company_status_hold' => false,
                'updated_at' => now(),
            ]);
    }

    private function releaseUsers(Company $company): int
    {
        return DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('company_status_hold', true)
            ->whereNotNull('held_status')
            ->update([
                'status' => DB::raw('held_status'),
                'held_status' => null,
                'company_status_hold' => false,
                'updated_at' => now(),
            ]);
    }
}
