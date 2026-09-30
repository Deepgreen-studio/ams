<?php

namespace App\Domains\Monitoring\Enums;

final class MonitoringPermission
{
    public const VIEW = 'monitoring.view';

    public const MANAGE = 'monitoring.manage';

    public const REALTIME = 'monitoring.realtime';

    public const API = 'monitoring.api';

    public const WEBHOOKS = 'monitoring.webhooks';

    public const QUEUE = 'monitoring.queue';

    public const INTEGRATIONS = 'monitoring.integrations';

    public const TIMELINE = 'monitoring.timeline';

    public const HISTORY = 'monitoring.history';

    public const ALERTS = 'monitoring.alerts';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::MANAGE,
            self::REALTIME,
            self::API,
            self::WEBHOOKS,
            self::QUEUE,
            self::INTEGRATIONS,
            self::TIMELINE,
            self::HISTORY,
            self::ALERTS,
        ];
    }
}
