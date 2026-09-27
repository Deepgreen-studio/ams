<?php

namespace App\Domains\Customers\Repositories;

use App\Domains\Customers\Models\Industry;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IndustryRepository extends BaseRepository
{
    public function __construct(Industry $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?Industry
    {
        /** @var Industry|null $industry */
        $industry = $this->model->newQuery()->where(function (Builder $builder) use ($identifier): void {
            $builder->where('uuid', $identifier)->orWhere('code', $identifier);
            if (ctype_digit($identifier)) {
                $builder->orWhere('id', (int) $identifier);
            }
        })->first();

        return $industry;
    }

    /**
     * @return Collection<int, Industry>
     */
    public function tree(): Collection
    {
        return $this->model->newQuery()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($query): void {
                $query->where('is_active', true)->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();
    }
}
