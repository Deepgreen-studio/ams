<?php

namespace App\Domains\Analytics\Repositories;

use App\Domains\Analytics\Enums\AnalyticsDashboardShareType;
use App\Domains\Analytics\Models\AnalyticsDashboardShare;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class AnalyticsDashboardShareRepository extends BaseRepository
{
    public function __construct(AnalyticsDashboardShare $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid): AnalyticsDashboardShare
    {
        /** @var AnalyticsDashboardShare $share */
        $share = $this->model->newQuery()->where('uuid', $uuid)->firstOrFail();

        return $share;
    }

    /**
     * @return Collection<int, AnalyticsDashboardShare>
     */
    public function forDashboard(int $dashboardId): Collection
    {
        return $this->model->newQuery()
            ->where('analytics_dashboard_id', $dashboardId)
            ->with('sharer:id,uuid,full_name,email')
            ->orderBy('share_type')
            ->orderBy('id')
            ->get();
    }

    public function findExisting(int $dashboardId, string $shareType, int $shareId): ?AnalyticsDashboardShare
    {
        /** @var AnalyticsDashboardShare|null $share */
        $share = $this->model->newQuery()
            ->where('analytics_dashboard_id', $dashboardId)
            ->where('share_type', $shareType)
            ->where('share_id', $shareId)
            ->first();

        return $share;
    }

    /**
     * @return list<AnalyticsDashboardShareType>
     */
    public function allowedTypes(): array
    {
        return AnalyticsDashboardShareType::cases();
    }
}
