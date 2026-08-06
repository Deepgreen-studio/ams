<?php

namespace App\Domains\Applications\Services;

use App\Domains\Applications\Enums\ApplicationConfigurationStatus;
use App\Domains\Applications\Enums\ApplicationConfigurationType;
use App\Domains\Applications\Events\ApplicationConfigurationCreated;
use App\Domains\Applications\Events\ApplicationConfigurationDeleted;
use App\Domains\Applications\Events\ApplicationConfigurationRestoredHistory;
use App\Domains\Applications\Events\ApplicationConfigurationUpdated;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationConfiguration;
use App\Domains\Applications\Models\ApplicationConfigurationHistory;
use App\Domains\Applications\Repositories\ApplicationConfigurationRepository;
use App\Domains\Applications\Repositories\ApplicationEnvironmentRepository;
use App\Domains\Applications\Repositories\ApplicationRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationConfigurationService
{
    public function __construct(
        private readonly ApplicationConfigurationRepository $configurationRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly ApplicationEnvironmentRepository $environmentRepository,
        private readonly ApplicationConfigurationValidator $validator
    ) {}

    public function resolveApplication(string $identifier): Application
    {
        return $this->applicationRepository->findByIdentifierOrFail($identifier);
    }

    /**
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        return $this->validator->catalogRules();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $applicationIdentifier, array $filters = []): LengthAwarePaginator
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $filters = $this->resolveEnvironmentFilter($application->id, $filters);

        return $this->configurationRepository->paginateForApplication($application->id, $filters);
    }

    /**
     * @return Collection<int, ApplicationConfiguration>
     */
    public function manager(string $applicationIdentifier, ?string $environmentIdentifier = null): Collection
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $environmentId = null;

        if ($environmentIdentifier !== null && $environmentIdentifier !== '') {
            $environment = $this->environmentRepository->findForApplication($application->id, $environmentIdentifier);
            $environmentId = $environment->id;
        }

        return $this->configurationRepository->managerCatalog($application->id, $environmentId);
    }

    public function find(string $applicationIdentifier, string $configurationIdentifier): ApplicationConfiguration
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->configurationRepository
            ->findForApplication($application->id, $configurationIdentifier)
            ->load([
                'application:id,uuid,name,slug',
                'environment:id,uuid,name,slug,type',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(string $applicationIdentifier, array $data, User $actor): ApplicationConfiguration
    {
        return DB::transaction(function () use ($applicationIdentifier, $data, $actor): ApplicationConfiguration {
            $application = $this->resolveApplication($applicationIdentifier);
            $type = ApplicationConfigurationType::from((string) $data['type']);
            $environmentId = $this->resolveEnvironmentId($application->id, $data['environment_id'] ?? null);

            if ($this->configurationRepository->typeExistsForScope($application->id, $environmentId, $type->value)) {
                throw new ApiException('A configuration of this type already exists for the selected scope.', 422);
            }

            $payload = $this->validator->validate($type, is_array($data['payload'] ?? null) ? $data['payload'] : $type->defaultPayload());
            $status = (string) ($data['status'] ?? ApplicationConfigurationStatus::Draft->value);

            $configuration = $this->configurationRepository->createConfiguration([
                'application_id' => $application->id,
                'environment_id' => $environmentId,
                'type' => $type->value,
                'name' => $data['name'] ?? $type->label(),
                'description' => $data['description'] ?? null,
                'payload' => $payload,
                'status' => $status,
                'version' => 1,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->recordHistory($configuration, 'Initial configuration created', $actor);
            event(new ApplicationConfigurationCreated($configuration, $actor));

            return $configuration;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $applicationIdentifier, string $configurationIdentifier, array $data, User $actor): ApplicationConfiguration
    {
        return DB::transaction(function () use ($applicationIdentifier, $configurationIdentifier, $data, $actor): ApplicationConfiguration {
            $application = $this->resolveApplication($applicationIdentifier);
            $configuration = $this->configurationRepository->findForApplication($application->id, $configurationIdentifier);

            $payloadData = $configuration->payload ?? [];
            if (array_key_exists('payload', $data)) {
                if (! is_array($data['payload'])) {
                    throw new ApiException('Payload must be a JSON object.', 422);
                }
                $payloadData = $this->mergeSensitivePayload($configuration, $data['payload']);
                $payloadData = $this->validator->validate($configuration->type, $payloadData);
            }

            $update = [
                'updated_by' => $actor->id,
                'version' => ((int) $configuration->version) + (array_key_exists('payload', $data) || array_key_exists('status', $data) ? 1 : 0),
            ];

            if (array_key_exists('name', $data)) {
                $update['name'] = $data['name'];
            }
            if (array_key_exists('description', $data)) {
                $update['description'] = $data['description'];
            }
            if (array_key_exists('status', $data)) {
                $update['status'] = $data['status'];
            }
            if (array_key_exists('is_active', $data)) {
                $update['is_active'] = (bool) $data['is_active'];
            }
            if (array_key_exists('payload', $data)) {
                $update['payload'] = $payloadData;
            }

            if (! array_key_exists('payload', $data) && ! array_key_exists('status', $data)) {
                unset($update['version']);
            }

            $updated = $this->configurationRepository->updateConfiguration($configuration, $update);

            if (array_key_exists('payload', $data) || array_key_exists('status', $data)) {
                $this->recordHistory(
                    $updated,
                    (string) ($data['change_summary'] ?? 'Configuration updated'),
                    $actor
                );
            }

            event(new ApplicationConfigurationUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $applicationIdentifier, string $configurationIdentifier, User $actor): void
    {
        DB::transaction(function () use ($applicationIdentifier, $configurationIdentifier, $actor): void {
            $application = $this->resolveApplication($applicationIdentifier);
            $configuration = $this->configurationRepository->findForApplication($application->id, $configurationIdentifier);
            $this->configurationRepository->updateConfiguration($configuration, ['updated_by' => $actor->id]);
            $configuration->delete();
            event(new ApplicationConfigurationDeleted($configuration, $actor));
        });
    }

    /**
     * @return Collection<int, ApplicationConfigurationHistory>
     */
    public function history(string $applicationIdentifier, string $configurationIdentifier): Collection
    {
        $configuration = $this->find($applicationIdentifier, $configurationIdentifier);

        return $this->configurationRepository->historyForConfiguration($configuration->id);
    }

    public function restoreHistory(
        string $applicationIdentifier,
        string $configurationIdentifier,
        string $historyIdentifier,
        User $actor
    ): ApplicationConfiguration {
        return DB::transaction(function () use ($applicationIdentifier, $configurationIdentifier, $historyIdentifier, $actor): ApplicationConfiguration {
            $configuration = $this->find($applicationIdentifier, $configurationIdentifier);
            $history = $this->configurationRepository->findHistory($configuration->id, $historyIdentifier);

            $payload = $this->validator->validate($configuration->type, is_array($history->payload) ? $history->payload : []);
            $updated = $this->configurationRepository->updateConfiguration($configuration, [
                'payload' => $payload,
                'status' => $history->status ?: $configuration->status,
                'version' => ((int) $configuration->version) + 1,
                'updated_by' => $actor->id,
            ]);

            $this->recordHistory($updated, 'Restored from history version '.$history->version, $actor);
            event(new ApplicationConfigurationRestoredHistory($updated, $actor, $history));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{valid: bool, errors: array<string, mixed>, normalized: array<string, mixed>|null}
     */
    public function validatePayload(string $type, array $payload): array
    {
        try {
            $typeEnum = ApplicationConfigurationType::from($type);
            $normalized = $this->validator->validate($typeEnum, $payload);

            return [
                'valid' => true,
                'errors' => [],
                'normalized' => $normalized,
            ];
        } catch (ApiException $exception) {
            return [
                'valid' => false,
                'errors' => $exception->getErrors() ?? [],
                'normalized' => null,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $flag
     */
    public function upsertFeatureFlag(
        string $applicationIdentifier,
        string $configurationIdentifier,
        array $flag,
        User $actor
    ): ApplicationConfiguration {
        $configuration = $this->find($applicationIdentifier, $configurationIdentifier);
        if (($configuration->type?->value ?? $configuration->type) !== ApplicationConfigurationType::FeatureFlags->value) {
            throw new ApiException('Feature flag updates require a feature_flags configuration.', 422);
        }

        $payload = is_array($configuration->payload) ? $configuration->payload : ['flags' => []];
        $flags = is_array($payload['flags'] ?? null) ? $payload['flags'] : [];
        $key = (string) ($flag['key'] ?? '');
        $found = false;

        foreach ($flags as $index => $existing) {
            if (($existing['key'] ?? null) === $key) {
                $flags[$index] = array_merge($existing, $flag);
                $found = true;
                break;
            }
        }

        if (! $found) {
            $flags[] = $flag;
        }

        return $this->update($applicationIdentifier, $configurationIdentifier, [
            'payload' => ['flags' => $flags],
            'change_summary' => ($found ? 'Updated' : 'Created').' feature flag '.$key,
        ], $actor);
    }

    public function toggleFeatureFlag(
        string $applicationIdentifier,
        string $configurationIdentifier,
        string $flagKey,
        bool $enabled,
        User $actor
    ): ApplicationConfiguration {
        $configuration = $this->find($applicationIdentifier, $configurationIdentifier);
        $payload = is_array($configuration->payload) ? $configuration->payload : ['flags' => []];
        $flags = is_array($payload['flags'] ?? null) ? $payload['flags'] : [];
        $updated = false;

        foreach ($flags as $index => $flag) {
            if (($flag['key'] ?? null) === $flagKey) {
                $flags[$index]['enabled'] = $enabled;
                $updated = true;
                break;
            }
        }

        if (! $updated) {
            throw new ApiException('Feature flag not found.', 404);
        }

        return $this->update($applicationIdentifier, $configurationIdentifier, [
            'payload' => ['flags' => $flags],
            'change_summary' => 'Toggled feature flag '.$flagKey.' to '.($enabled ? 'enabled' : 'disabled'),
        ], $actor);
    }

    protected function recordHistory(ApplicationConfiguration $configuration, string $summary, User $actor): void
    {
        $this->configurationRepository->createHistory([
            'configuration_id' => $configuration->id,
            'version' => $configuration->version,
            'payload' => $configuration->payload,
            'status' => $configuration->status?->value ?? $configuration->status,
            'change_summary' => $summary,
            'created_by' => $actor->id,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    protected function mergeSensitivePayload(ApplicationConfiguration $configuration, array $incoming): array
    {
        $type = $configuration->type instanceof ApplicationConfigurationType
            ? $configuration->type
            : ApplicationConfigurationType::from((string) $configuration->type);

        if (! $type->isSensitive()) {
            return $incoming;
        }

        $existing = is_array($configuration->payload) ? $configuration->payload : [];
        foreach ($incoming as $key => $value) {
            if ($value === null || $value === '' || $value === '********') {
                if (array_key_exists($key, $existing)) {
                    $incoming[$key] = $existing[$key];
                }
            }
        }

        return $incoming;
    }

    protected function resolveEnvironmentId(int $applicationId, mixed $identifier): ?int
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        $environment = $this->environmentRepository->findForApplication($applicationId, (string) $identifier);

        return $environment->id;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function resolveEnvironmentFilter(int $applicationId, array $filters): array
    {
        if (! array_key_exists('environment', $filters) && ! array_key_exists('environment_id', $filters)) {
            return $filters;
        }

        $identifier = $filters['environment'] ?? $filters['environment_id'] ?? null;
        if ($identifier === null || $identifier === '' || $identifier === 'null') {
            $filters['environment_id'] = null;

            return $filters;
        }

        if (is_numeric($identifier)) {
            $filters['environment_id'] = (int) $identifier;

            return $filters;
        }

        $environment = $this->environmentRepository->findForApplication($applicationId, (string) $identifier);
        $filters['environment_id'] = $environment->id;

        return $filters;
    }
}
