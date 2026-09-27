<?php

namespace App\Domains\Customers\Enums;

final class CustomerContactPermission
{
    public const VIEW = 'customer-contacts.view';

    public const CREATE = 'customer-contacts.create';

    public const UPDATE = 'customer-contacts.update';

    public const DELETE = 'customer-contacts.delete';

    public const RESTORE = 'customer-contacts.restore';

    public const EXPORT = 'customer-contacts.export';

    public const IMPORT = 'customer-contacts.import';
}
