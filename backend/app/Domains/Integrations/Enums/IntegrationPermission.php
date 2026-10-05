<?php

namespace App\Domains\Integrations\Enums;

final class IntegrationPermission
{
    public const VIEW = 'integrations.view';

    public const CREATE = 'integrations.create';

    public const UPDATE = 'integrations.update';

    public const DELETE = 'integrations.delete';

    public const RESTORE = 'integrations.restore';

    public const FORCE_DELETE = 'integrations.force-delete';

    public const VIEW_TRASH = 'integrations.view-trash';

    public const MANAGE = 'integrations.manage';

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
            self::MANAGE,
        ];
    }
}
