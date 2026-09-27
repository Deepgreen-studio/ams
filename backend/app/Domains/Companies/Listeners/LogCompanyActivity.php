<?php

namespace App\Domains\Companies\Listeners;

use App\Domains\Audit\Services\SystemEventService;
use App\Domains\Companies\Events\BrandingUpdated;
use App\Domains\Companies\Events\CompanyCreated;
use App\Domains\Companies\Events\CompanyDeleted;
use App\Domains\Companies\Events\CompanyRestored;
use App\Domains\Companies\Events\CompanyStatusChanged;
use App\Domains\Companies\Events\CompanyUpdated;
use App\Domains\Companies\Events\DepartmentCreated;
use App\Domains\Companies\Events\DepartmentUpdated;
use App\Domains\Companies\Events\LocationCreated;
use App\Domains\Companies\Events\TeamCreated;

class LogCompanyActivity
{
    public function __construct(private readonly SystemEventService $systemEvents) {}

    public function handleCompanyCreated(CompanyCreated $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->company)
            ->event('created')
            ->withProperties(['event' => 'company_created', 'name' => $event->company->company_name])
            ->log('Company created');

        $this->systemEvents->record('company.created', 'companies', $this->companyPayload($event->company, $event->actor), 'info');
    }

    public function handleCompanyUpdated(CompanyUpdated $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->company)
            ->event('updated')
            ->withProperties(['event' => 'company_updated', 'name' => $event->company->company_name])
            ->log('Company updated');
    }

    public function handleCompanyDeleted(CompanyDeleted $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->company)
            ->event('deleted')
            ->withProperties(['event' => 'company_deleted', 'name' => $event->company->company_name])
            ->log('Company deleted');

        $this->systemEvents->record('company.deleted', 'companies', $this->companyPayload($event->company, $event->actor), 'warning');
    }

    public function handleCompanyStatusChanged(CompanyStatusChanged $event): void
    {
        $from = $this->statusLabel($event->from);
        $to = $this->statusLabel($event->to);

        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->company)
            ->event('status_changed')
            ->withProperties([
                'event' => 'status_changed',
                'name' => $event->company->company_name,
                'old_status' => $event->from,
                'new_status' => $event->to,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("Status updated from {$from} to {$to}");

        $this->systemEvents->record('company.status_changed', 'companies', [
            ...$this->companyPayload($event->company, $event->actor),
            'old_status' => $event->from,
            'new_status' => $event->to,
        ], 'warning');
    }

    public function handleCompanyRestored(CompanyRestored $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->company)
            ->event('restored')
            ->withProperties([
                'event' => 'company_restored',
                'name' => $event->company->company_name,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Company restored');

        $this->systemEvents->record('company.restored', 'companies', $this->companyPayload($event->company, $event->actor), 'info');
    }

    public function handleBrandingUpdated(BrandingUpdated $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->company)
            ->withProperties([
                'event' => 'branding_updated',
                'logo' => $event->company->logo,
                'favicon' => $event->company->favicon,
            ])
            ->log('Branding updated');
    }

    public function handleDepartmentCreated(DepartmentCreated $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->department)
            ->withProperties(['event' => 'department_created', 'name' => $event->department->name])
            ->log('Department created');
    }

    public function handleDepartmentUpdated(DepartmentUpdated $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->department)
            ->withProperties(['event' => 'department_updated', 'name' => $event->department->name])
            ->log('Department updated');
    }

    public function handleTeamCreated(TeamCreated $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->team)
            ->withProperties(['event' => 'team_created', 'name' => $event->team->name])
            ->log('Team created');
    }

    public function handleLocationCreated(LocationCreated $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->location)
            ->withProperties(['event' => 'location_created', 'name' => $event->location->branch_name])
            ->log('Location created');
    }

    /**
     * @return array<string, mixed>
     */
    private function companyPayload(mixed $company, mixed $actor): array
    {
        return [
            'company_id' => $company->id,
            'company_uuid' => $company->uuid,
            'company_name' => $company->company_name,
            'actor_id' => $actor->id,
            'important' => true,
        ];
    }

    private function statusLabel(string $status): string
    {
        return ucfirst(str_replace('_', ' ', $status));
    }
}
