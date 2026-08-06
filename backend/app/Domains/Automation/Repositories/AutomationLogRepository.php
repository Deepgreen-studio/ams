<?php

namespace App\Domains\Automation\Repositories;

use App\Domains\Automation\Models\AutomationLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AutomationLogRepository extends BaseRepository
{
    public function __construct(AutomationLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $query = $this->model->newQuery()
            ->with(['rule:id,uuid,name,trigger_type,event_key'])
            ->latest('id');

        if (! blank($filters['automation_rule_id'] ?? null)) {
            $query->where('automation_rule_id', (int) $filters['automation_rule_id']);
        }
        if (! blank($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (! blank($filters['event_key'] ?? null)) {
            $query->where('event_key', $filters['event_key']);
        }
        if (! blank($filters['trigger_type'] ?? null)) {
            $query->where('trigger_type', $filters['trigger_type']);
        }
        if (! blank($filters['search'] ?? null)) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('message', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%")
                    ->orWhere('event_key', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
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
            'skipped' => $this->model->newQuery()->where('status', 'skipped')->count(),
            'running' => $this->model->newQuery()->where('status', 'running')->count(),
        ];
    }
}
