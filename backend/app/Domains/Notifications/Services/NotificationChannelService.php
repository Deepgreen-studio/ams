<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Events\NotificationChannelUpdated;
use App\Domains\Notifications\Models\NotificationChannel;
use App\Domains\Notifications\Repositories\NotificationChannelRepository;
use App\Models\User;
use App\Shared\Services\NotificationManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class NotificationChannelService
{
    public function __construct(
        private readonly NotificationChannelRepository $channelRepository,
        private readonly NotificationManager $notificationManager,
    ) {}

    /**
     * @return Collection<int, NotificationChannel>
     */
    public function list(): Collection
    {
        return $this->channelRepository->allOrdered();
    }

    /**
     * @return array<string, mixed>
     */
    public function matrix(): array
    {
        $channels = $this->channelRepository->allOrdered();
        $matrix = [];

        foreach ($channels as $channel) {
            $matrix[$channel->key] = (bool) $channel->is_enabled;
        }

        return $this->notificationManager->channelMatrix($matrix);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): NotificationChannel
    {
        return DB::transaction(function () use ($identifier, $data, $actor): NotificationChannel {
            $channel = $this->channelRepository->findByIdentifierOrFail($identifier);

            $payload = ['updated_by' => $actor->id];
            if (array_key_exists('is_enabled', $data)) {
                $payload['is_enabled'] = (bool) $data['is_enabled'];
            }
            if (array_key_exists('config', $data)) {
                $payload['config'] = $data['config'];
            }
            if (array_key_exists('description', $data) && ! $channel->is_system) {
                $payload['description'] = $data['description'];
            }

            /** @var NotificationChannel $updated */
            $updated = $this->channelRepository->update($channel->id, $payload);
            event(new NotificationChannelUpdated($updated, $actor));

            return $updated->fresh();
        });
    }

    public function seedDefaults(?User $actor = null): void
    {
        foreach (NotificationChannelEnum::cases() as $index => $channel) {
            $existing = $this->channelRepository->findByKey($channel->value);
            if ($existing) {
                continue;
            }

            $this->channelRepository->create([
                'key' => $channel->value,
                'name' => $channel->label(),
                'description' => $channel->description(),
                'is_enabled' => $channel->defaultEnabled(),
                'is_implemented' => $channel->isImplemented(),
                'is_system' => true,
                'sort_order' => ($index + 1) * 10,
                'config' => [],
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);
        }
    }
}
