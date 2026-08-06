<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Models\SupportSlaHoliday;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SupportSlaHolidayRepository
{
    public function findByIdentifierOrFail(string $identifier): SupportSlaHoliday
    {
        return SupportSlaHoliday::query()
            ->where(function (Builder $query) use ($identifier): void {
                $query->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = SupportSlaHoliday::query()->with([
            'company:id,uuid,company_name',
            'calendar:id,uuid,name',
        ]);

        if (! blank($filters['company_id'] ?? null)) {
            $query->where(function (Builder $builder) use ($filters): void {
                $builder->where('company_id', $filters['company_id'])
                    ->orWhereNull('company_id');
            });
        }

        if (! blank($filters['calendar_id'] ?? null)) {
            $query->where('support_sla_calendar_id', $filters['calendar_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! blank($filters['search'] ?? null)) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderBy('holiday_date')
            ->paginate((int) ($filters['per_page'] ?? 50));
    }

    /**
     * @return Collection<int, SupportSlaHoliday>
     */
    public function forCalendarScope(?int $calendarId, ?int $companyId): Collection
    {
        return SupportSlaHoliday::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($calendarId, $companyId): void {
                if ($calendarId !== null) {
                    $query->where('support_sla_calendar_id', $calendarId);
                }

                $query->orWhere(function (Builder $nested) use ($companyId): void {
                    $nested->whereNull('support_sla_calendar_id');
                    if ($companyId !== null) {
                        $nested->where(function (Builder $companyScope) use ($companyId): void {
                            $companyScope->where('company_id', $companyId)->orWhereNull('company_id');
                        });
                    } else {
                        $nested->whereNull('company_id');
                    }
                });
            })
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupportSlaHoliday
    {
        return SupportSlaHoliday::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SupportSlaHoliday $holiday, array $data): SupportSlaHoliday
    {
        $holiday->fill($data);
        $holiday->save();

        return $holiday->refresh();
    }

    public function delete(SupportSlaHoliday $holiday): void
    {
        $holiday->delete();
    }
}
