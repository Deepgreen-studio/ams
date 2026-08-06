<?php

namespace App\Domains\Settings\Repositories;

use App\Domains\Settings\Models\ConfigurationLog;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConfigurationRepository extends BaseRepository
{
    public function __construct(ConfigurationLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function logChange(array $attributes): ConfigurationLog
    {
        /** @var ConfigurationLog $log */
        $log = $this->create($attributes);

        return $log;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateFiltered(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));
        $query = $this->model->newQuery()->with('changer:id,uuid,full_name,email');

        if (! empty($filters['group'])) {
            $query->where('group', $filters['group']);
        }

        if (! empty($filters['setting_key'])) {
            $query->where('setting_key', 'like', '%'.$filters['setting_key'].'%');
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }
}
