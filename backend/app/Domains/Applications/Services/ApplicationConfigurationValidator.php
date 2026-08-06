<?php

namespace App\Domains\Applications\Services;

use App\Domains\Applications\Enums\ApplicationConfigurationType;
use App\Shared\Exceptions\ApiException;
use Illuminate\Support\Facades\Validator;

class ApplicationConfigurationValidator
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validate(ApplicationConfigurationType|string $type, array $payload): array
    {
        $typeEnum = $type instanceof ApplicationConfigurationType
            ? $type
            : ApplicationConfigurationType::from((string) $type);

        $rules = $this->rulesFor($typeEnum);
        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            throw new ApiException('Configuration validation failed.', 422, $validator->errors()->toArray());
        }

        return $validator->validated();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesFor(ApplicationConfigurationType $type): array
    {
        return match ($type) {
            ApplicationConfigurationType::FeatureFlags => [
                'flags' => ['required', 'array'],
                'flags.*.key' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z_][A-Za-z0-9_.-]*$/'],
                'flags.*.enabled' => ['required', 'boolean'],
                'flags.*.description' => ['nullable', 'string', 'max:500'],
                'flags.*.rollout' => ['nullable', 'integer', 'min:0', 'max:100'],
            ],
            ApplicationConfigurationType::RemoteConfig => [
                'values' => ['required', 'array'],
            ],
            ApplicationConfigurationType::MaintenanceMode => [
                'enabled' => ['required', 'boolean'],
                'message' => ['nullable', 'string', 'max:1000'],
                'allowed_ips' => ['nullable', 'array'],
                'allowed_ips.*' => ['ip'],
                'starts_at' => ['nullable', 'date'],
                'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            ],
            ApplicationConfigurationType::AppSettings => [
                'settings' => ['required', 'array'],
            ],
            ApplicationConfigurationType::ApiConfiguration => [
                'base_url' => ['nullable', 'url', 'max:500'],
                'timeout' => ['required', 'integer', 'min:1', 'max:300'],
                'retry_attempts' => ['required', 'integer', 'min:0', 'max:10'],
                'headers' => ['nullable', 'array'],
            ],
            ApplicationConfigurationType::FirebaseKeys => [
                'api_key' => ['nullable', 'string', 'max:500'],
                'project_id' => ['nullable', 'string', 'max:255'],
                'app_id' => ['nullable', 'string', 'max:255'],
                'messaging_sender_id' => ['nullable', 'string', 'max:255'],
                'storage_bucket' => ['nullable', 'string', 'max:255'],
            ],
            ApplicationConfigurationType::AnalyticsKeys => [
                'provider' => ['nullable', 'string', 'max:100'],
                'measurement_id' => ['nullable', 'string', 'max:255'],
                'api_secret' => ['nullable', 'string', 'max:500'],
                'enabled' => ['required', 'boolean'],
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function catalogRules(): array
    {
        $catalog = [];
        foreach (ApplicationConfigurationType::cases() as $type) {
            $catalog[$type->value] = [
                'label' => $type->label(),
                'sensitive' => $type->isSensitive(),
                'default_payload' => $type->defaultPayload(),
                'rules' => array_keys($this->rulesFor($type)),
            ];
        }

        return $catalog;
    }
}
