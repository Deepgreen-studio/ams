<?php

namespace App\Domains\Ai\Repositories;

use App\Domains\Ai\Models\AiSetting;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AiSettingRepository extends BaseRepository
{
    public function __construct(AiSetting $model)
    {
        parent::__construct($model);
    }

    /**
     * @return Collection<int, AiSetting>
     */
    public function forCompany(?int $companyId = null): Collection
    {
        return $this->model->newQuery()
            ->when($companyId !== null, function (Builder $builder) use ($companyId): void {
                $builder->where(function (Builder $inner) use ($companyId): void {
                    $inner->where('company_id', $companyId)->orWhereNull('company_id');
                });
            }, function (Builder $builder): void {
                $builder->whereNull('company_id');
            })
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    public function findByKey(string $key, ?int $companyId = null): ?AiSetting
    {
        return $this->model->newQuery()
            ->where('key', $key)
            ->when($companyId !== null, function (Builder $builder) use ($companyId): void {
                $builder->where(function (Builder $inner) use ($companyId): void {
                    $inner->where('company_id', $companyId)->orWhereNull('company_id');
                });
            }, function (Builder $builder): void {
                $builder->whereNull('company_id');
            })
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 1 ELSE 0 END')
            ->first();
    }

    /**
     * @param  mixed  $value
     */
    public function upsert(string $key, $value, string $group = 'general', ?int $companyId = null): AiSetting
    {
        $existing = $this->model->newQuery()
            ->where('key', $key)
            ->where(function (Builder $builder) use ($companyId): void {
                if ($companyId === null) {
                    $builder->whereNull('company_id');
                } else {
                    $builder->where('company_id', $companyId);
                }
            })
            ->first();

        if ($existing) {
            $existing->update([
                'group' => $group,
                'value' => $value,
            ]);

            return $existing->fresh();
        }

        /** @var AiSetting $created */
        $created = $this->create([
            'company_id' => $companyId,
            'group' => $group,
            'key' => $key,
            'value' => $value,
        ]);

        return $created;
    }
}
