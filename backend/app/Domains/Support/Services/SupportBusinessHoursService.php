<?php

namespace App\Domains\Support\Services;

use App\Domains\Support\Models\SupportSlaCalendar;
use App\Domains\Support\Models\SupportSlaHoliday;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SupportBusinessHoursService
{
    private const WEEKDAYS = [
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
        7 => 'sunday',
    ];

    /**
     * @param  Collection<int, SupportSlaHoliday>|null  $holidays
     */
    public function addBusinessMinutes(
        CarbonImmutable $start,
        int $minutes,
        ?SupportSlaCalendar $calendar = null,
        ?Collection $holidays = null
    ): CarbonImmutable {
        if ($minutes <= 0) {
            return $start;
        }

        if ($calendar === null || empty($calendar->business_hours)) {
            return $start->addMinutes($minutes);
        }

        $timezone = $calendar->timezone ?: 'UTC';
        $cursor = $start->timezone($timezone);
        $remaining = $minutes;
        $holidaySet = $this->holidayKeys($holidays ?? collect());
        $guard = 0;

        while ($remaining > 0 && $guard < 20000) {
            $guard++;

            if ($this->isHoliday($cursor, $holidaySet) || ! $this->hasWindow($calendar, $cursor)) {
                $cursor = $this->nextDayStart($cursor);

                continue;
            }

            [$windowStart, $windowEnd] = $this->windowFor($calendar, $cursor);

            if ($cursor->lt($windowStart)) {
                $cursor = $windowStart;
            }

            if ($cursor->gte($windowEnd)) {
                $cursor = $this->nextDayStart($cursor);

                continue;
            }

            $available = $cursor->diffInMinutes($windowEnd);
            if ($available >= $remaining) {
                return $cursor->addMinutes($remaining)->utc();
            }

            $remaining -= $available;
            $cursor = $this->nextDayStart($cursor);
        }

        return $cursor->utc();
    }

    /**
     * Remaining business minutes until due date (negative if overdue).
     *
     * @param  Collection<int, SupportSlaHoliday>|null  $holidays
     */
    public function remainingBusinessMinutes(
        CarbonImmutable $from,
        CarbonImmutable $due,
        ?SupportSlaCalendar $calendar = null,
        ?Collection $holidays = null
    ): int {
        if ($calendar === null || empty($calendar->business_hours)) {
            return (int) $from->diffInMinutes($due, false);
        }

        if ($due->lte($from)) {
            return -1 * $this->elapsedBusinessMinutes($due, $from, $calendar, $holidays);
        }

        return $this->elapsedBusinessMinutes($from, $due, $calendar, $holidays);
    }

    /**
     * @param  Collection<int, SupportSlaHoliday>|null  $holidays
     */
    public function elapsedBusinessMinutes(
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?SupportSlaCalendar $calendar = null,
        ?Collection $holidays = null
    ): int {
        if ($to->lte($from)) {
            return 0;
        }

        if ($calendar === null || empty($calendar->business_hours)) {
            return (int) $from->diffInMinutes($to);
        }

        $timezone = $calendar->timezone ?: 'UTC';
        $cursor = $from->timezone($timezone);
        $end = $to->timezone($timezone);
        $holidaySet = $this->holidayKeys($holidays ?? collect());
        $total = 0;
        $guard = 0;

        while ($cursor->lt($end) && $guard < 20000) {
            $guard++;

            if ($this->isHoliday($cursor, $holidaySet) || ! $this->hasWindow($calendar, $cursor)) {
                $cursor = $this->nextDayStart($cursor);

                continue;
            }

            [$windowStart, $windowEnd] = $this->windowFor($calendar, $cursor);
            $segmentStart = $cursor->lt($windowStart) ? $windowStart : $cursor;
            $segmentEnd = $end->lt($windowEnd) ? $end : $windowEnd;

            if ($segmentStart->lt($segmentEnd)) {
                $total += (int) $segmentStart->diffInMinutes($segmentEnd);
            }

            $cursor = $this->nextDayStart($cursor);
        }

        return $total;
    }

    /**
     * @param  Collection<int, SupportSlaHoliday>  $holidays
     * @return array<string, true>
     */
    protected function holidayKeys(Collection $holidays): array
    {
        $keys = [];

        foreach ($holidays as $holiday) {
            $date = $holiday->holiday_date?->format('Y-m-d');
            if ($date) {
                $keys[$date] = true;
            }

            if ($holiday->is_recurring && $holiday->holiday_date) {
                $keys['recur-'.$holiday->holiday_date->format('m-d')] = true;
            }
        }

        return $keys;
    }

    /**
     * @param  array<string, true>  $holidaySet
     */
    protected function isHoliday(CarbonImmutable $date, array $holidaySet): bool
    {
        return isset($holidaySet[$date->format('Y-m-d')])
            || isset($holidaySet['recur-'.$date->format('m-d')]);
    }

    protected function hasWindow(SupportSlaCalendar $calendar, CarbonImmutable $date): bool
    {
        $day = self::WEEKDAYS[$date->dayOfWeekIso] ?? null;
        $hours = $calendar->business_hours[$day] ?? null;

        return is_array($hours) && count($hours) >= 2 && ! blank($hours[0]) && ! blank($hours[1]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function windowFor(SupportSlaCalendar $calendar, CarbonImmutable $date): array
    {
        $day = self::WEEKDAYS[$date->dayOfWeekIso];
        $hours = $calendar->business_hours[$day];
        $start = $date->setTimeFromTimeString((string) $hours[0]);
        $end = $date->setTimeFromTimeString((string) $hours[1]);

        return [$start, $end];
    }

    protected function nextDayStart(CarbonImmutable $date): CarbonImmutable
    {
        return $date->addDay()->startOfDay();
    }
}
