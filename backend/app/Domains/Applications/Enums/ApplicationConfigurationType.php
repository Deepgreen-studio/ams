<?php

namespace App\Domains\Applications\Enums;

enum ApplicationConfigurationType: string
{
    case FeatureFlags = 'feature_flags';
    case RemoteConfig = 'remote_config';
    case MaintenanceMode = 'maintenance_mode';
    case AppSettings = 'app_settings';
    case ApiConfiguration = 'api_configuration';
    case FirebaseKeys = 'firebase_keys';
    case AnalyticsKeys = 'analytics_keys';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::FeatureFlags => 'Feature Flags',
            self::RemoteConfig => 'Remote Config',
            self::MaintenanceMode => 'Maintenance Mode',
            self::AppSettings => 'App Settings',
            self::ApiConfiguration => 'API Configuration',
            self::FirebaseKeys => 'Firebase Keys',
            self::AnalyticsKeys => 'Analytics Keys',
        };
    }

    public function isSensitive(): bool
    {
        return match ($this) {
            self::FirebaseKeys, self::AnalyticsKeys => true,
            default => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultPayload(): array
    {
        return match ($this) {
            self::FeatureFlags => [
                'flags' => [],
            ],
            self::RemoteConfig => [
                'values' => [],
            ],
            self::MaintenanceMode => [
                'enabled' => false,
                'message' => 'We are currently performing maintenance. Please try again later.',
                'allowed_ips' => [],
                'starts_at' => null,
                'ends_at' => null,
            ],
            self::AppSettings => [
                'settings' => [],
            ],
            self::ApiConfiguration => [
                'base_url' => null,
                'timeout' => 30,
                'retry_attempts' => 3,
                'headers' => [],
            ],
            self::FirebaseKeys => [
                'api_key' => null,
                'project_id' => null,
                'app_id' => null,
                'messaging_sender_id' => null,
                'storage_bucket' => null,
            ],
            self::AnalyticsKeys => [
                'provider' => null,
                'measurement_id' => null,
                'api_secret' => null,
                'enabled' => false,
            ],
        };
    }
}
