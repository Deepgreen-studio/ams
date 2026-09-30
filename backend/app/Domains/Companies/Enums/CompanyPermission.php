<?php

namespace App\Domains\Companies\Enums;

final class CompanyPermission
{
    public const VIEW = 'companies.view';

    public const CREATE = 'companies.create';

    public const UPDATE = 'companies.update';

    public const DELETE = 'companies.delete';

    public const RESTORE = 'companies.restore';

    public const FORCE_DELETE = 'companies.force-delete';

    public const VIEW_TRASH = 'companies.view-trash';

    public const CONSOLE = 'companies.console';

    public const PROFILE = 'companies.profile';

    public const MANAGE = 'companies.manage';

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
            self::CONSOLE,
            self::PROFILE,
            self::MANAGE,
        ];
    }
}
