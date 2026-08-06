<?php

namespace App\Domains\Companies\Enums;

final class CompanyPermission
{
    public const VIEW = 'companies.view';

    public const CREATE = 'companies.create';

    public const UPDATE = 'companies.update';

    public const DELETE = 'companies.delete';

    public const RESTORE = 'companies.restore';

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
            self::MANAGE,
        ];
    }
}
