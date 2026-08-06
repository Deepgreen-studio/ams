<?php

namespace App\Domains\Settings\Services;

use App\Domains\Settings\Events\ConfigurationChanged;
use App\Domains\Settings\Events\SettingsUpdated;
use App\Domains\Settings\Models\SystemSetting;
use App\Domains\Settings\Repositories\ConfigurationRepository;
use App\Domains\Settings\Repositories\SystemSettingRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SystemSettingService
{
    public function __construct(
        private readonly SystemSettingRepository $settingRepository,
        private readonly ConfigurationRepository $configurationRepository
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allGrouped(bool $maskSecrets = true): array
    {
        $grouped = [];
        foreach ($this->settingRepository->allSettings() as $setting) {
            $grouped[$setting->group][$setting->key] = $this->presentSetting($setting, $maskSecrets);
        }

        return $grouped;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGroup(string $group, bool $maskSecrets = true): array
    {
        $settings = [];
        foreach ($this->settingRepository->getByGroup($group) as $setting) {
            $settings[$setting->key] = $this->presentSetting($setting, $maskSecrets);
        }

        return $settings;
    }

    public function getValue(string $group, string $key, mixed $default = null): mixed
    {
        $map = $this->settingRepository->cachedMap();
        if (! isset($map[$group][$key])) {
            return $default;
        }

        return $this->castStoredValue(
            $map[$group][$key]['value'],
            $map[$group][$key]['type'],
            (bool) $map[$group][$key]['is_encrypted']
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function updateGroup(string $group, array $values, User $actor, ?string $ip = null): array
    {
        return DB::transaction(function () use ($group, $values, $actor, $ip): array {
            $updatedKeys = [];

            foreach ($values as $key => $value) {
                $setting = $this->settingRepository->findByGroupAndKey($group, (string) $key);
                if (! $setting) {
                    continue;
                }

                if ($setting->is_encrypted && ($value === '********' || $value === null || $value === '')) {
                    continue;
                }

                $oldRaw = $setting->value;
                $stored = $this->serializeValue($value, $setting->type, $setting->is_encrypted);

                if ((string) $oldRaw === (string) $stored) {
                    continue;
                }

                $setting->fill([
                    'value' => $stored,
                    'updated_by' => $actor->id,
                ]);
                $setting->save();

                $this->configurationRepository->logChange([
                    'setting_key' => $setting->fullKey(),
                    'group' => $group,
                    'old_value' => $setting->is_encrypted ? '[encrypted]' : $oldRaw,
                    'new_value' => $setting->is_encrypted ? '[encrypted]' : $stored,
                    'changed_by' => $actor->id,
                    'ip_address' => $ip,
                ]);

                $updatedKeys[] = $setting->fullKey();
            }

            $this->settingRepository->forgetCache();

            if ($updatedKeys !== []) {
                event(new SettingsUpdated($group, $updatedKeys, $actor));
                event(new ConfigurationChanged($group, $updatedKeys, $actor));
            }

            return $this->getGroup($group);
        });
    }

    /**
     * @return Collection<int, SystemSetting>
     */
    public function seedDefaults(User $actor): Collection
    {
        foreach ($this->defaultDefinitions() as $definition) {
            $existing = $this->settingRepository->findByGroupAndKey($definition['group'], $definition['key']);
            if ($existing) {
                continue;
            }

            $this->settingRepository->create([
                'group' => $definition['group'],
                'key' => $definition['key'],
                'value' => $this->serializeValue($definition['value'], $definition['type'], $definition['is_encrypted'] ?? false),
                'type' => $definition['type'],
                'description' => $definition['description'] ?? null,
                'is_public' => $definition['is_public'] ?? false,
                'is_encrypted' => $definition['is_encrypted'] ?? false,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        }

        $this->settingRepository->forgetCache();

        return $this->settingRepository->allSettings();
    }

    /**
     * @return array<string, mixed>
     */
    public function systemInformation(): array
    {
        return [
            'app_name' => $this->getValue('general', 'app_name', config('app.name')),
            'app_url' => $this->getValue('general', 'app_url', config('app.url')),
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'timezone' => $this->getValue('general', 'timezone', config('app.timezone')),
            'locale' => $this->getValue('localization', 'language', config('app.locale')),
            'maintenance_mode' => (bool) $this->getValue('general', 'maintenance_mode', false),
            'cache_driver' => $this->getValue('cache', 'driver', config('cache.default')),
            'queue_connection' => $this->getValue('queue', 'default_connection', config('queue.default')),
            'storage_disk' => $this->getValue('storage', 'default_disk', config('filesystems.default')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentSetting(SystemSetting $setting, bool $maskSecrets): array
    {
        $value = $this->castStoredValue($setting->value, $setting->type, $setting->is_encrypted);

        if ($maskSecrets && $setting->is_encrypted) {
            $value = filled($setting->value) ? '********' : null;
        }

        return [
            'uuid' => $setting->uuid,
            'group' => $setting->group,
            'key' => $setting->key,
            'value' => $value,
            'type' => $setting->type,
            'description' => $setting->description,
            'is_public' => $setting->is_public,
            'is_encrypted' => $setting->is_encrypted,
            'full_key' => $setting->fullKey(),
        ];
    }

    protected function castStoredValue(mixed $value, string $type, bool $encrypted): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($encrypted || $type === 'encrypted') {
            try {
                return Crypt::decryptString((string) $value);
            } catch (\Throwable) {
                return null;
            }
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    protected function serializeValue(mixed $value, string $type, bool $encrypted): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($encrypted || $type === 'encrypted') {
            if ($value === '********') {
                return null;
            }

            return Crypt::encryptString((string) $value);
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'integer' => (string) (int) $value,
            'float' => (string) (float) $value,
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function defaultDefinitions(): array
    {
        return [
            ['group' => 'general', 'key' => 'app_name', 'value' => config('app.name', 'AMS'), 'type' => 'string', 'description' => 'Application display name', 'is_public' => true],
            ['group' => 'general', 'key' => 'app_url', 'value' => config('app.url'), 'type' => 'url', 'description' => 'Application base URL', 'is_public' => true],
            ['group' => 'general', 'key' => 'timezone', 'value' => config('app.timezone', 'UTC'), 'type' => 'string', 'description' => 'Default timezone', 'is_public' => true],
            ['group' => 'general', 'key' => 'language', 'value' => 'en', 'type' => 'string', 'description' => 'Default language', 'is_public' => true],
            ['group' => 'general', 'key' => 'currency', 'value' => 'USD', 'type' => 'string', 'description' => 'Default currency', 'is_public' => true],
            ['group' => 'general', 'key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string', 'description' => 'Date format', 'is_public' => true],
            ['group' => 'general', 'key' => 'time_format', 'value' => 'H:i', 'type' => 'string', 'description' => 'Time format', 'is_public' => true],
            ['group' => 'general', 'key' => 'maintenance_mode', 'value' => false, 'type' => 'boolean', 'description' => 'Enable maintenance mode'],

            ['group' => 'email', 'key' => 'smtp_host', 'value' => env('MAIL_HOST'), 'type' => 'string', 'description' => 'SMTP host'],
            ['group' => 'email', 'key' => 'smtp_port', 'value' => (int) env('MAIL_PORT', 587), 'type' => 'integer', 'description' => 'SMTP port'],
            ['group' => 'email', 'key' => 'smtp_username', 'value' => env('MAIL_USERNAME'), 'type' => 'string', 'description' => 'SMTP username'],
            ['group' => 'email', 'key' => 'smtp_password', 'value' => null, 'type' => 'encrypted', 'description' => 'SMTP password', 'is_encrypted' => true],
            ['group' => 'email', 'key' => 'encryption', 'value' => env('MAIL_ENCRYPTION', 'tls'), 'type' => 'string', 'description' => 'SMTP encryption'],
            ['group' => 'email', 'key' => 'from_name', 'value' => env('MAIL_FROM_NAME', config('app.name')), 'type' => 'string', 'description' => 'Sender name'],
            ['group' => 'email', 'key' => 'from_email', 'value' => env('MAIL_FROM_ADDRESS'), 'type' => 'email', 'description' => 'Sender email'],

            ['group' => 'storage', 'key' => 'default_disk', 'value' => config('filesystems.media_library_disk', 'public'), 'type' => 'string', 'description' => 'Default media disk'],
            ['group' => 'storage', 'key' => 'public_disk', 'value' => 'public', 'type' => 'string', 'description' => 'Public storage disk'],
            ['group' => 'storage', 'key' => 'private_disk', 'value' => 'local', 'type' => 'string', 'description' => 'Private storage disk'],
            ['group' => 'storage', 'key' => 'max_upload_kb', 'value' => 10240, 'type' => 'integer', 'description' => 'Max upload size in KB'],
            ['group' => 'storage', 'key' => 'allowed_extensions', 'value' => ['jpg', 'jpeg', 'png', 'svg', 'pdf', 'docx', 'xlsx', 'zip'], 'type' => 'json', 'description' => 'Allowed upload extensions'],
            ['group' => 'storage', 'key' => 'cloud_provider', 'value' => null, 'type' => 'string', 'description' => 'Future cloud provider (s3|gcs|azure)'],

            ['group' => 'security', 'key' => 'password_min_length', 'value' => 8, 'type' => 'integer', 'description' => 'Minimum password length'],
            ['group' => 'security', 'key' => 'password_require_symbols', 'value' => true, 'type' => 'boolean', 'description' => 'Require password symbols'],
            ['group' => 'security', 'key' => 'session_timeout_minutes', 'value' => 120, 'type' => 'integer', 'description' => 'Session timeout minutes'],
            ['group' => 'security', 'key' => 'login_max_attempts', 'value' => 5, 'type' => 'integer', 'description' => 'Max login attempts'],
            ['group' => 'security', 'key' => 'api_rate_limit', 'value' => 60, 'type' => 'integer', 'description' => 'API rate limit per minute'],

            ['group' => 'api', 'key' => 'enabled', 'value' => true, 'type' => 'boolean', 'description' => 'API access enabled'],
            ['group' => 'api', 'key' => 'default_page_size', 'value' => 15, 'type' => 'integer', 'description' => 'Default API page size'],
            ['group' => 'api', 'key' => 'max_page_size', 'value' => 100, 'type' => 'integer', 'description' => 'Maximum API page size'],
            ['group' => 'api', 'key' => 'token_expiration_minutes', 'value' => 10080, 'type' => 'integer', 'description' => 'API token expiration minutes'],

            ['group' => 'queue', 'key' => 'default_connection', 'value' => config('queue.default', 'database'), 'type' => 'string', 'description' => 'Default queue connection'],
            ['group' => 'queue', 'key' => 'default_queue', 'value' => 'default', 'type' => 'string', 'description' => 'Default queue name'],
            ['group' => 'queue', 'key' => 'retry_attempts', 'value' => 3, 'type' => 'integer', 'description' => 'Job retry attempts'],
            ['group' => 'queue', 'key' => 'job_timeout', 'value' => 90, 'type' => 'integer', 'description' => 'Job timeout seconds'],

            ['group' => 'cache', 'key' => 'driver', 'value' => config('cache.default', 'database'), 'type' => 'string', 'description' => 'Cache driver'],
            ['group' => 'cache', 'key' => 'ttl_seconds', 'value' => 3600, 'type' => 'integer', 'description' => 'Default cache TTL seconds'],

            ['group' => 'localization', 'key' => 'language', 'value' => 'en', 'type' => 'string', 'description' => 'Default language', 'is_public' => true],
            ['group' => 'localization', 'key' => 'timezone', 'value' => 'UTC', 'type' => 'string', 'description' => 'Localization timezone', 'is_public' => true],
            ['group' => 'localization', 'key' => 'currency', 'value' => 'USD', 'type' => 'string', 'description' => 'Localization currency', 'is_public' => true],

            ['group' => 'notifications', 'key' => 'email_enabled', 'value' => true, 'type' => 'boolean', 'description' => 'Email notifications enabled'],
            ['group' => 'notifications', 'key' => 'push_enabled', 'value' => false, 'type' => 'boolean', 'description' => 'Push notifications enabled'],
            ['group' => 'notifications', 'key' => 'in_app_enabled', 'value' => true, 'type' => 'boolean', 'description' => 'In-app notifications enabled'],
        ];
    }
}
