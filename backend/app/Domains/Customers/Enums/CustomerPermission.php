<?php

namespace App\Domains\Customers\Enums;

final class CustomerPermission
{
    public const VIEW = 'customers.view';

    public const CREATE = 'customers.create';

    public const UPDATE = 'customers.update';

    public const DELETE = 'customers.delete';

    public const RESTORE = 'customers.restore';

    public const FORCE_DELETE = 'customers.force-delete';

    public const VIEW_TRASH = 'customers.view-trash';

    public const EXPORT = 'customers.export';

    public const IMPORT = 'customers.import';

    public const ANONYMIZE = 'customers.anonymize';

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
            self::FORCE_DELETE,
            self::VIEW_TRASH,
            self::EXPORT,
            self::IMPORT,
            self::ANONYMIZE,
        ];
    }
}
