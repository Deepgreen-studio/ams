<?php

namespace App\Domains\Customers\Enums;

final class CustomerSubscriptionPermission
{
    public const VIEW = 'customer-subscriptions.view';

    public const CREATE = 'customer-subscriptions.create';

    public const UPDATE = 'customer-subscriptions.update';

    public const DELETE = 'customer-subscriptions.delete';

    public const RESTORE = 'customer-subscriptions.restore';

    public const CANCEL = 'customer-subscriptions.cancel';
}
