<?php

namespace App\Domains\Customers\Enums;

final class CustomerPermission
{
    public const VIEW = 'customers.view';

    public const CREATE = 'customers.create';

    public const UPDATE = 'customers.update';

    public const DELETE = 'customers.delete';

    public const RESTORE = 'customers.restore';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::RESTORE,
        ];
    }
}
