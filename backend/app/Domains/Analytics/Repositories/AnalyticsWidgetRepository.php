<?php

namespace App\Domains\Analytics\Repositories;

use App\Domains\Analytics\Models\AnalyticsWidget;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class AnalyticsWidgetRepository extends BaseRepository
{
    public function __construct(AnalyticsWidget $model)
    {
        parent::__construct($model);
    }

    public function findByUuidOrFail(string $uuid, bool $withTrashed = false): AnalyticsWidget
    {
        $query = $this->model->newQuery();

        if ($withTrashed) {
            $query->withTrashed();
        }

        /** @var AnalyticsWidget $widget */
        $widget = $query->where('uuid', $uuid)->firstOrFail();

        return $widget;
    }

    /**
     * @return Collection<int, AnalyticsWidget>
     */
    public function forDashboard(int $dashboardId, bool $visibleOnly = false): Collection
    {
        $query = $this->model->newQuery()
            ->where('analytics_dashboard_id', $dashboardId)
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($visibleOnly) {
            $query->where('is_visible', true);
        }

        return $query->get();
    }

    public function uniqueKey(int $dashboardId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, '_') ?: 'widget';
        $key = $base;
        $i = 1;

        while ($this->keyExists($dashboardId, $key, $ignoreId)) {
            $key = $base.'_'.$i;
            $i++;
        }

        return $key;
    }

    protected function keyExists(int $dashboardId, string $key, ?int $ignoreId): bool
    {
        $query = $this->model->newQuery()
            ->withTrashed()
            ->where('analytics_dashboard_id', $dashboardId)
            ->where('key', $key);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
