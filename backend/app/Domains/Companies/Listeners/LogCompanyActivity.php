<?php

namespace App\Domains\Companies\Listeners;

use App\Domains\Companies\Events\BrandingUpdated;
use App\Domains\Companies\Events\CompanyCreated;
use App\Domains\Companies\Events\CompanyDeleted;
use App\Domains\Companies\Events\CompanyUpdated;
use App\Domains\Companies\Events\DepartmentCreated;
use App\Domains\Companies\Events\DepartmentUpdated;
use App\Domains\Companies\Events\LocationCreated;
use App\Domains\Companies\Events\TeamCreated;

class LogCompanyActivity
{
    public function handleCompanyCreated(CompanyCreated $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->company)
            ->withProperties(['event' => 'company_created', 'name' => $event->company->company_name])
            ->log('Company created');
    }

    public function handleCompanyUpdated(CompanyUpdated $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->company)
            ->withProperties(['event' => 'company_updated', 'name' => $event->company->company_name])
            ->log('Company updated');
    }

    public function handleCompanyDeleted(CompanyDeleted $event): void
    {
        activity('companies')
            ->causedBy($event->actor)
            ->performedOn($event->company)
            ->withProperties(['event' => 'company_deleted', 'name' => $event->company->company_name])
            ->log('Company deleted');
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
}
