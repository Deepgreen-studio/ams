<?php

namespace App\Domains\Integrations\Enums;

final class SyncPermission
{
    public const VIEW = 'sync.view';

    public const CONFIGS = 'sync.configs';

    public const HISTORY = 'sync.history';

    public const LOGS = 'sync.logs';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CONFIGS,
            self::HISTORY,
            self::LOGS,
        ];
    }
}
