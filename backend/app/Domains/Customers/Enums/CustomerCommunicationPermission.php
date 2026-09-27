<?php

namespace App\Domains\Customers\Enums;

final class CustomerCommunicationPermission
{
    public const VIEW = 'customer-communications.view';

    public const CREATE = 'customer-communications.create';

    public const UPDATE = 'customer-communications.update';

    public const DELETE = 'customer-communications.delete';

    public const RESTORE = 'customer-communications.restore';
}
