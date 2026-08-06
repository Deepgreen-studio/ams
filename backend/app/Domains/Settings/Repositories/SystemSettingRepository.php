<?php

namespace App\Domains\Settings\Repositories;

use App\Domains\Settings\Models\SystemSetting;
use App\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SystemSettingRepository extends BaseRepository
{
    public const CACHE_KEY = 'ams.system_settings';

    public function __construct(SystemSetting $model)
    {
        parent::__construct($model);
    }

    public function findByIdentifier(string $identifier): ?SystemSetting
    {
        /** @var SystemSetting|null $setting */
        $setting = $this->model->newQuery()
            ->where('uuid', $identifier)
            ->when(ctype_digit($identifier), fn ($q) => $q->orWhere('id', (int) $identifier))
            ->first();

        return $setting;
    }

    public function findByGroupAndKey(string $group, string $key): ?SystemSetting
    {
        /** @var SystemSetting|null $setting */
        $setting = $this->model->newQuery()
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting;
    }

    /**
     * @return Collection<int, SystemSetting>
     */
    public function getByGroup(string $group): Collection
    {
        return $this->model->newQuery()
            ->where('group', $group)
            ->orderBy('key')
            ->get();
    }

    /**
     * @return Collection<int, SystemSetting>
     */
    public function allSettings(): Collection
    {
        return $this->model->newQuery()->orderBy('group')->orderBy('key')->get();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function cachedMap(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $map = [];
            foreach ($this->allSettings() as $setting) {
                $map[$setting->group][$setting->key] = [
                    'uuid' => $setting->uuid,
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'is_public' => $setting->is_public,
                    'is_encrypted' => $setting->is_encrypted,
                ];
            }

            return $map;
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function upsertSetting(string $group, string $key, array $attributes): SystemSetting
    {
        /** @var SystemSetting $setting */
        $setting = $this->model->newQuery()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            $attributes
        );

        $this->forgetCache();

        return $setting->refresh();
    }
}
