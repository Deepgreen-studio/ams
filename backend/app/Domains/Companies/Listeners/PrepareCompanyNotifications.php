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

/** Placeholder for future company notification workflows. */
class PrepareCompanyNotifications
{
    public function handleCompanyCreated(CompanyCreated $event): void {}

    public function handleCompanyUpdated(CompanyUpdated $event): void {}

    public function handleCompanyDeleted(CompanyDeleted $event): void {}

    public function handleBrandingUpdated(BrandingUpdated $event): void {}

    public function handleDepartmentCreated(DepartmentCreated $event): void {}

    public function handleDepartmentUpdated(DepartmentUpdated $event): void {}

    public function handleTeamCreated(TeamCreated $event): void {}

    public function handleLocationCreated(LocationCreated $event): void {}
}
