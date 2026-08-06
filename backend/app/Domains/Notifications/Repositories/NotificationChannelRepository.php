<?php

namespace App\Domains\Notifications\Repositories;

use App\Domains\Notifications\Models\NotificationChannel;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NotificationChannelRepository extends BaseRepository
{
    public function __construct(NotificationChannel $model)
    {
        parent::__construct($model);
    }

    public function findByKey(string $key): ?NotificationChannel
    {
        /** @var NotificationChannel|null $channel */
        $channel = $this->model->newQuery()->where('key', $key)->first();

        return $channel;
    }

    public function findByIdentifierOrFail(string $identifier): NotificationChannel
    {
        /** @var NotificationChannel|null $channel */
        $channel = $this->model->newQuery()
            ->where(function (Builder $builder) use ($identifier): void {
                $builder->where('uuid', $identifier)->orWhere('key', $identifier);
                if (ctype_digit($identifier)) {
                    $builder->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        if (! $channel) {
            abort(404, 'Notification channel not found.');
        }

        return $channel;
    }

    /**
     * @return Collection<int, NotificationChannel>
     */
    public function allOrdered(): Collection
    {
        return $this->model->newQuery()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, NotificationChannel>
     */
    public function enabled(): Collection
    {
        return $this->model->newQuery()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get();
    }
}
