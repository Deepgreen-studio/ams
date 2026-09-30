<?php

namespace App\Domains\Analytics\Enums;

final class AnalyticsPermission
{
    public const VIEW = 'analytics.view';

    public const CREATE = 'analytics.create';

    public const UPDATE = 'analytics.update';

    public const DELETE = 'analytics.delete';

    public const EXPORT = 'analytics.export';

    public const MANAGE = 'analytics.manage';

    public const DASHBOARDS = 'analytics.dashboards';

    public const TEMPLATES = 'analytics.templates';

    public const REPORTS = 'analytics.reports';

    public const SAVED_REPORTS = 'analytics.saved-reports';

    public const SAVED_VIEWS = 'analytics.saved-views';

    public const EVENTS = 'analytics.events';

    public const BUSINESS = 'analytics.business';

    public const EXECUTIVE = 'analytics.executive';

    public const SECURITY = 'analytics.security';

    public const OPERATIONAL = 'analytics.operational';

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
            self::EXPORT,
            self::MANAGE,
            self::DASHBOARDS,
            self::TEMPLATES,
            self::REPORTS,
            self::SAVED_REPORTS,
            self::SAVED_VIEWS,
            self::EVENTS,
            self::BUSINESS,
            self::EXECUTIVE,
            self::SECURITY,
            self::OPERATIONAL,
        ];
    }
}
