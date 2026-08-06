<?php

namespace App\Domains\Support\Repositories;

use App\Domains\Support\Models\SupportSlaCalendar;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SupportSlaCalendarRepository
{
    public function findByIdentifierOrFail(string $identifier): SupportSlaCalendar
    {
        return SupportSlaCalendar::query()
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
        $query = SupportSlaCalendar::query()->with(['company:id,uuid,company_name']);

        if (! blank($filters['company_id'] ?? null)) {
            $query->where(function (Builder $builder) use ($filters): void {
                $builder->where('company_id', $filters['company_id'])
                    ->orWhereNull('company_id');
            });
        }

        if (($filters['scope'] ?? null) === 'global') {
            $query->whereNull('company_id');
        }

        if (($filters['scope'] ?? null) === 'company' && ! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', $filters['company_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query->orderByDesc('is_default')->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function resolveDefault(?int $companyId): ?SupportSlaCalendar
    {
        if ($companyId !== null) {
            $companyDefault = SupportSlaCalendar::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();

            if ($companyDefault) {
                return $companyDefault;
            }
        }

        return SupportSlaCalendar::query()
            ->whereNull('company_id')
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupportSlaCalendar
    {
        return SupportSlaCalendar::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SupportSlaCalendar $calendar, array $data): SupportSlaCalendar
    {
        $calendar->fill($data);
        $calendar->save();

        return $calendar->refresh();
    }
}
