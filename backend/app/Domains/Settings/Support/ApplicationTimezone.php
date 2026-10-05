<?php

namespace App\Domains\Settings\Support;

use App\Domains\Settings\Services\SystemSettingService;
use DateTimeZone;
use Throwable;

/**
 * The timezone saved in General Settings.
 *
 * Instants stay stored in UTC. This name is the clock used for display,
 * schedules, and defaults when a record has no timezone of its own.
 */
class ApplicationTimezone
{
    public static function name(): string
    {
        try {
            $value = app(SystemSettingService::class)->getValue('general', 'timezone', 'UTC');

            if (! is_string($value) || $value === '') {
                return 'UTC';
            }

            new DateTimeZone($value);

            return $value;
        } catch (Throwable) {
            return 'UTC';
        }
    }
}
