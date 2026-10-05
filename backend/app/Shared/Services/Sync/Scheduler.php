<?php

namespace App\Shared\Services\Sync;

use Cron\CronExpression;
use Illuminate\Support\Carbon;

class Scheduler
{
    /**
     * Whether a cron expression is due at the given moment.
     */
    public function isDue(string $expression, ?Carbon $now = null, ?string $timeZone = null): bool
    {
        $now ??= now();

        if ($timeZone) {
            $now = $now->copy()->timezone($timeZone);
        }

        if (! CronExpression::isValidExpression($expression)) {
            return false;
        }

        return (new CronExpression($expression))->isDue($now->toDateTimeString(), $timeZone);
    }

    public function nextRunDate(string $expression, ?Carbon $now = null, ?string $timeZone = null): ?Carbon
    {
        if (! CronExpression::isValidExpression($expression)) {
            return null;
        }

        $current = ($now ?? now())->toDateTime();
        $next = (new CronExpression($expression))->getNextRunDate($current, 0, false, $timeZone);

        return Carbon::instance($next);
    }

    /**
     * @return array<string, string>
     */
    public function commonExpressions(): array
    {
        return [
            'every_minute' => '* * * * *',
            'hourly' => '0 * * * *',
            'daily' => '0 0 * * *',
            'weekly' => '0 0 * * 0',
            'monthly' => '0 0 1 * *',
        ];
    }
}
