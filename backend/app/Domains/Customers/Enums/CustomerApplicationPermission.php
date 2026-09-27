<?php

namespace App\Domains\Customers\Enums;

final class CustomerApplicationPermission
{
    public const VIEW = 'customer-applications.view';

    public const CREATE = 'customer-applications.create';

    public const UPDATE = 'customer-applications.update';

    public const DELETE = 'customer-applications.delete';

    public const RESTORE = 'customer-applications.restore';
}
