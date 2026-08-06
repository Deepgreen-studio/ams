<?php

namespace App\Domains\Ai\Services;

use App\Domains\Ai\Enums\AiFeature;
use App\Domains\Ai\Enums\AiProviderDriver;
use App\Domains\Ai\Enums\AiPromptStatus;
use App\Domains\Ai\Events\AiProviderCreated;
use App\Domains\Ai\Events\AiProviderDeleted;
use App\Domains\Ai\Events\AiProviderUpdated;
use App\Domains\Ai\Models\AiProvider;
use App\Domains\Ai\Repositories\AiProviderRepository;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiProviderService
{
    public function __construct(
        private readonly AiProviderRepository $providerRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly AiProviderManager $providerManager,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['company_id']) && ! is_numeric($filters['company_id'])) {
            $filters['company_id'] = $this->companyRepository
                ->findByIdentifierOrFail((string) $filters['company_id'])->id;
        }

        return $this->providerRepository->paginateFiltered($filters);
    }

    public function find(string $identifier): AiProvider
    {
        return $this->providerRepository->findByIdentifierOrFail($identifier)
            ->load(['company:id,uuid,company_name', 'creator:id,uuid,full_name,email']);
    }

    /**
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        return [
            'drivers' => collect(AiProviderDriver::cases())->map(fn (AiProviderDriver $driver) => [
                'value' => $driver->value,
                'label' => $driver->label(),
                'registered' => array_key_exists($driver->value, config('ai.drivers', [])),
            ])->values()->all(),
            'features' => collect(AiFeature::cases())->map(fn (AiFeature $feature) => [
                'value' => $feature->value,
                'label' => $feature->label(),
                'enabled' => (bool) config('ai.features.'.$feature->value, true),
            ])->values()->all(),
            'prompt_statuses' => collect(AiPromptStatus::cases())->map(fn (AiPromptStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values()->all(),
            'registered_drivers' => $this->providerManager->registeredDrivers(),
            'config' => [
                'default_driver' => config('ai.default_driver'),
                'timeout' => config('ai.timeout'),
                'max_tokens' => config('ai.max_tokens'),
                'daily_token_limit' => config('ai.daily_token_limit'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): AiProvider
    {
        return DB::transaction(function () use ($data, $actor): AiProvider {
            $payload = $this->preparePayload($data);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            if (! empty($payload['is_default'])) {
                $this->providerRepository->clearDefaults($payload['company_id'] ?? null);
            }

            /** @var AiProvider $provider */
            $provider = $this->providerRepository->create($payload);
            $fresh = $this->find($provider->uuid);
            event(new AiProviderCreated($fresh, $actor));

            return $fresh;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): AiProvider
    {
        return DB::transaction(function () use ($identifier, $data, $actor): AiProvider {
            $provider = $this->providerRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (! empty($payload['is_default'])) {
                $this->providerRepository->clearDefaults(
                    $payload['company_id'] ?? $provider->company_id,
                    $provider->id
                );
            }

            $this->providerRepository->update($provider->id, $payload);
            $fresh = $this->find($provider->uuid);
            event(new AiProviderUpdated($fresh, $actor));

            return $fresh;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        $provider = $this->providerRepository->findByIdentifierOrFail($identifier);
        event(new AiProviderDeleted($provider, $actor));
        $this->providerRepository->delete($provider->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function testConnection(string $identifier): array
    {
        $provider = $this->providerRepository->findByIdentifierOrFail($identifier);
        $driver = $this->providerManager->forProvider($provider);
        $result = $driver->testConnection();

        $this->providerRepository->update($provider->id, [
            'health_status' => $result->healthy ? 'healthy' : 'unhealthy',
            'last_health_check_at' => now(),
            'status' => $result->healthy ? 'active' : $provider->status,
        ]);

        return [
            'ok' => $result->healthy,
            'message' => $result->message,
            'meta' => $result->details,
            'latency_ms' => $result->latencyMs,
            'driver' => $driver->driver(),
            'provider' => [
                'uuid' => $provider->uuid,
                'name' => $provider->name,
                'slug' => $provider->slug,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $payload = [];

        foreach ([
            'name', 'slug', 'driver', 'status', 'base_url', 'default_model', 'embedding_model',
            'authentication_type', 'health_status',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        if (array_key_exists('company_id', $data)) {
            $companyId = $data['company_id'];
            if ($companyId === null || $companyId === '') {
                $payload['company_id'] = null;
            } elseif (is_numeric($companyId)) {
                $payload['company_id'] = (int) $companyId;
            } else {
                $payload['company_id'] = $this->companyRepository
                    ->findByIdentifierOrFail((string) $companyId)->id;
            }
        }

        if (array_key_exists('credentials', $data) && is_array($data['credentials'])) {
            $payload['credentials'] = $data['credentials'];
        }

        if (array_key_exists('config', $data)) {
            $payload['config'] = is_array($data['config']) ? $data['config'] : [];
        }

        foreach (['timeout_seconds', 'retry_attempts'] as $intField) {
            if (array_key_exists($intField, $data) && $data[$intField] !== null) {
                $payload[$intField] = (int) $data[$intField];
            }
        }

        foreach (['is_default', 'is_enabled'] as $boolField) {
            if (array_key_exists($boolField, $data)) {
                $payload[$boolField] = filter_var($data[$boolField], FILTER_VALIDATE_BOOLEAN);
            }
        }

        if (! $isUpdate) {
            if (empty($payload['name'])) {
                throw new ApiException('Provider name is required.', 422);
            }
            if (empty($payload['driver'])) {
                throw new ApiException('Provider driver is required.', 422);
            }
            if (! array_key_exists($payload['driver'], config('ai.drivers', []))) {
                throw new ApiException('AI driver is not registered.', 422);
            }
            $payload['slug'] = $payload['slug'] ?? Str::slug($payload['name']);
            $payload['status'] = $payload['status'] ?? 'inactive';
            $payload['authentication_type'] = $payload['authentication_type'] ?? 'api_key';
            $payload['is_enabled'] = $payload['is_enabled'] ?? true;
            $payload['is_default'] = $payload['is_default'] ?? false;
            $payload['timeout_seconds'] = $payload['timeout_seconds'] ?? (int) config('ai.timeout', 30);
            $payload['retry_attempts'] = $payload['retry_attempts'] ?? 2;
        } elseif (isset($payload['driver']) && ! array_key_exists($payload['driver'], config('ai.drivers', []))) {
            throw new ApiException('AI driver is not registered.', 422);
        }

        return $payload;
    }
}
