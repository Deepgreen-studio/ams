<?php

namespace App\Domains\Customers\Enums;

final class CustomerLicensePermission
{
    public const VIEW = 'customer-licenses.view';

    public const CREATE = 'customer-licenses.create';

    public const UPDATE = 'customer-licenses.update';

    public const DELETE = 'customer-licenses.delete';

    public const RESTORE = 'customer-licenses.restore';

    public const REVOKE = 'customer-licenses.revoke';
}
