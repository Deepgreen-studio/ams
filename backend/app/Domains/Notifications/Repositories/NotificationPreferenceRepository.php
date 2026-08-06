<?php

namespace App\Domains\Notifications\Repositories;

use App\Domains\Notifications\Models\NotificationPreference;
use App\Models\User;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class NotificationPreferenceRepository extends BaseRepository
{
    public function __construct(NotificationPreference $model)
    {
        parent::__construct($model);
    }

    /**
     * @return Collection<int, NotificationPreference>
     */
    public function forUser(User $user): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->get();
    }

    public function forUserEvent(User $user, string $eventKey): ?NotificationPreference
    {
        /** @var NotificationPreference|null $preference */
        $preference = $this->model->newQuery()
            ->where('user_id', $user->id)
            ->where('event_key', $eventKey)
            ->first();

        return $preference;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function upsertForUserEvent(User $user, string $eventKey, array $attributes): NotificationPreference
    {
        /** @var NotificationPreference $preference */
        $preference = $this->model->newQuery()->updateOrCreate(
            [
                'user_id' => $user->id,
                'event_key' => $eventKey,
            ],
            $attributes
        );

        return $preference;
    }
}
