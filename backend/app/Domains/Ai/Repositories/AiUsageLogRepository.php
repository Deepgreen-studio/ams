<?php

namespace App\Domains\Ai\Repositories;

use App\Domains\Ai\Models\AiUsageLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AiUsageLogRepository extends BaseRepository
{
    public function __construct(AiUsageLog $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?AiUsageLog
    {
        /** @var AiUsageLog|null $log */
        $log = $this->model->newQuery()
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        return $log;
    }

    public function findByIdentifierOrFail(string $identifier): AiUsageLog
    {
        $log = $this->findByIdentifier($identifier);
        if (! $log) {
            abort(404, 'AI usage log not found.');
        }

        return $log;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        return $this->filteredQuery($filters)
            ->with([
                'user:id,uuid,full_name,email',
                'provider:id,uuid,name,driver,slug',
                'conversation:id,uuid,title,feature',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function analytics(?int $days = 30): array
    {
        $since = now()->subDays(max(1, $days ?? 30));

        $totals = $this->model->newQuery()
            ->where('created_at', '>=', $since)
            ->selectRaw('COUNT(*) as requests')
            ->selectRaw('SUM(tokens_in) as tokens_in')
            ->selectRaw('SUM(tokens_out) as tokens_out')
            ->selectRaw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count')
            ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count')
            ->selectRaw('AVG(latency_ms) as avg_latency_ms')
            ->first();

        $byFeature = $this->model->newQuery()
            ->where('created_at', '>=', $since)
            ->select('feature', DB::raw('COUNT(*) as total'), DB::raw('SUM(tokens_in + tokens_out) as tokens'))
            ->groupBy('feature')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'feature' => $row->feature instanceof \BackedEnum ? $row->feature->value : $row->feature,
                'total' => (int) $row->total,
                'tokens' => (int) $row->tokens,
            ])
            ->values()
            ->all();

        $byDriver = $this->model->newQuery()
            ->where('created_at', '>=', $since)
            ->select('driver', DB::raw('COUNT(*) as total'), DB::raw('SUM(tokens_in + tokens_out) as tokens'))
            ->groupBy('driver')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'driver' => $row->driver,
                'total' => (int) $row->total,
                'tokens' => (int) $row->tokens,
            ])
            ->values()
            ->all();

        $daily = $this->model->newQuery()
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'), DB::raw('SUM(tokens_in + tokens_out) as tokens'))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'total' => (int) $row->total,
                'tokens' => (int) $row->tokens,
            ])
            ->values()
            ->all();

        return [
            'period_days' => $days ?? 30,
            'requests' => (int) ($totals->requests ?? 0),
            'tokens_in' => (int) ($totals->tokens_in ?? 0),
            'tokens_out' => (int) ($totals->tokens_out ?? 0),
            'success_count' => (int) ($totals->success_count ?? 0),
            'failed_count' => (int) ($totals->failed_count ?? 0),
            'avg_latency_ms' => round((float) ($totals->avg_latency_ms ?? 0), 2),
            'by_feature' => $byFeature,
            'by_driver' => $byDriver,
            'daily' => $daily,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function statistics(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'success' => $this->model->newQuery()->where('status', 'success')->count(),
            'failed' => $this->model->newQuery()->where('status', 'failed')->count(),
            'tokens_in' => (int) $this->model->newQuery()->sum('tokens_in'),
            'tokens_out' => (int) $this->model->newQuery()->sum('tokens_out'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function filteredQuery(array $filters = []): Builder
    {
        $query = $this->model->newQuery()->latest('id');

        if (! blank($filters['company_id'] ?? null)) {
            $query->where('company_id', (int) $filters['company_id']);
        }
        if (! blank($filters['feature'] ?? null)) {
            $query->where('feature', $filters['feature']);
        }
        if (! blank($filters['operation'] ?? null)) {
            $query->where('operation', $filters['operation']);
        }
        if (! blank($filters['driver'] ?? null)) {
            $query->where('driver', $filters['driver']);
        }
        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('error_message', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('request_id', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
