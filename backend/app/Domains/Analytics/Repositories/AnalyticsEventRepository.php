<?php

namespace App\Domains\Analytics\Repositories;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsEventRepository extends BaseRepository
{
    public function __construct(AnalyticsEvent $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): AnalyticsEvent
    {
        /** @var AnalyticsEvent $event */
        $event = $this->model->newQuery()
            ->where('uuid', $uuid)
            ->firstOrFail();

        return $event;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 25), 100));

        return $this->filteredQuery($filters)
            ->with([
                'company:id,uuid,company_name',
                'user:id,uuid,full_name,email',
                'application:id,uuid,name',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['event_name'])) {
            $query->where('event_name', $filters['event_name']);
        }

        if (! empty($filters['event_source'])) {
            $query->where('event_source', $filters['event_source']);
        }

        if (! empty($filters['application_id'])) {
            $query->where('application_id', (int) $filters['application_id']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('event_name', 'like', "%{$search}%")
                    ->orWhere('event_source', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%");
            });
        }

        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        if ($from) {
            $query->where('occurred_at', '>=', Carbon::parse((string) $from)->startOfDay());
        }

        if ($to) {
            $query->where('occurred_at', '<=', Carbon::parse((string) $to)->endOfDay());
        }

        $sortBy = in_array($filters['sort_by'] ?? '', ['occurred_at', 'event_name', 'category', 'created_at'], true)
            ? $filters['sort_by']
            : 'occurred_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function countByCategory(array $filters = []): array
    {
        $rows = $this->filteredQuery($filters)
            ->reorder()
            ->select('category', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->all();

        $result = [];
        foreach (AnalyticsCategory::cases() as $category) {
            $result[$category->value] = (int) ($rows[$category->value] ?? 0);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{date: string, count: int}>
     */
    public function dailyTrend(array $filters = []): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', occurred_at)"
            : 'DATE(occurred_at)';

        /** @var Collection<int, object{day: string, aggregate: int|string}> $rows */
        $rows = $this->filteredQuery($filters)
            ->reorder()
            ->selectRaw("{$dateExpression} as day, COUNT(*) as aggregate")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return $rows->map(fn ($row): array => [
            'date' => (string) $row->day,
            'count' => (int) $row->aggregate,
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function topEventNames(array $filters = [], int $limit = 10): array
    {
        return $this->filteredQuery($filters)
            ->reorder()
            ->select('event_name', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('event_name')
            ->orderByDesc('aggregate')
            ->limit($limit)
            ->pluck('aggregate', 'event_name')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }
}
