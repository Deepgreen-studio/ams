<?php

use App\Domains\Integrations\Providers\IntegrationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\DomainServiceProvider;

return [
    AppServiceProvider::class,
    DomainServiceProvider::class,
    IntegrationServiceProvider::class,
];
