<?php

namespace App\Domains\Applications\Services;

use App\Domains\Applications\Enums\ApplicationEnvironmentHealthStatus;
use App\Domains\Applications\Enums\ApplicationEnvironmentStatus;
use App\Domains\Applications\Events\ApplicationEnvironmentCreated;
use App\Domains\Applications\Events\ApplicationEnvironmentDeleted;
use App\Domains\Applications\Events\ApplicationEnvironmentHealthChecked;
use App\Domains\Applications\Events\ApplicationEnvironmentSwitched;
use App\Domains\Applications\Events\ApplicationEnvironmentUpdated;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Domains\Applications\Repositories\ApplicationEnvironmentRepository;
use App\Domains\Applications\Repositories\ApplicationRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ApplicationEnvironmentService
{
    public function __construct(
        private readonly ApplicationEnvironmentRepository $environmentRepository,
        private readonly ApplicationRepository $applicationRepository
    ) {}

    public function resolveApplication(string $identifier): Application
    {
        return $this->applicationRepository->findByIdentifierOrFail($identifier);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $applicationIdentifier, array $filters = []): LengthAwarePaginator
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->environmentRepository->paginateForApplication($application->id, $filters);
    }

    /**
     * @return Collection<int, ApplicationEnvironment>
     */
    public function dashboard(string $applicationIdentifier): Collection
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->environmentRepository->dashboardForApplication($application->id);
    }

    public function find(string $applicationIdentifier, string $environmentIdentifier): ApplicationEnvironment
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->environmentRepository
            ->findForApplication($application->id, $environmentIdentifier)
            ->load([
                'application:id,uuid,name,slug,status',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(string $applicationIdentifier, array $data, User $actor): ApplicationEnvironment
    {
        return DB::transaction(function () use ($applicationIdentifier, $data, $actor): ApplicationEnvironment {
            $application = $this->resolveApplication($applicationIdentifier);
            $payload = $this->preparePayload($data);
            $payload['application_id'] = $application->id;
            $payload['slug'] = $this->resolveUniqueSlug(
                $application->id,
                $payload['slug'] ?? null,
                $payload['name'],
                $payload['type']
            );

            if ($this->environmentRepository->typeExists($application->id, (string) $payload['type'])) {
                throw new ApiException('An environment of this type already exists for the application.', 422);
            }

            $payload['status'] = $payload['status'] ?? ApplicationEnvironmentStatus::Active->value;
            $payload['health_status'] = $payload['health_status'] ?? ApplicationEnvironmentHealthStatus::Unknown->value;
            $payload['is_current'] = (bool) ($payload['is_current'] ?? false);
            $payload['variables'] = $this->normalizeVariables($data['variables'] ?? []);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            if ($payload['is_current']) {
                $this->environmentRepository->clearCurrentForApplication($application->id);
            }

            $environment = $this->environmentRepository->createEnvironment($payload);
            event(new ApplicationEnvironmentCreated($environment, $actor));

            return $environment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $applicationIdentifier, string $environmentIdentifier, array $data, User $actor): ApplicationEnvironment
    {
        return DB::transaction(function () use ($applicationIdentifier, $environmentIdentifier, $data, $actor): ApplicationEnvironment {
            $application = $this->resolveApplication($applicationIdentifier);
            $environment = $this->environmentRepository->findForApplication($application->id, $environmentIdentifier);
            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('type', $payload)
                && $this->environmentRepository->typeExists($application->id, (string) $payload['type'], $environment->id)
            ) {
                throw new ApiException('An environment of this type already exists for the application.', 422);
            }

            if (array_key_exists('slug', $payload) || array_key_exists('name', $payload) || array_key_exists('type', $payload)) {
                $payload['slug'] = $this->resolveUniqueSlug(
                    $application->id,
                    $payload['slug'] ?? null,
                    $payload['name'] ?? $environment->name,
                    (string) ($payload['type'] ?? $environment->type?->value ?? $environment->type),
                    $environment->id
                );
            }

            if (array_key_exists('variables', $data)) {
                $payload['variables'] = $this->mergeVariables(
                    is_array($environment->variables) ? $environment->variables : [],
                    $data['variables']
                );
            }

            if (array_key_exists('is_current', $payload) && $payload['is_current']) {
                $this->environmentRepository->clearCurrentForApplication($application->id, $environment->id);
            }

            $updated = $this->environmentRepository->updateEnvironment($environment, $payload);
            event(new ApplicationEnvironmentUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $applicationIdentifier, string $environmentIdentifier, User $actor): void
    {
        DB::transaction(function () use ($applicationIdentifier, $environmentIdentifier, $actor): void {
            $application = $this->resolveApplication($applicationIdentifier);
            $environment = $this->environmentRepository->findForApplication($application->id, $environmentIdentifier);
            $this->environmentRepository->updateEnvironment($environment, [
                'updated_by' => $actor->id,
                'is_current' => false,
            ]);
            $environment->delete();
            event(new ApplicationEnvironmentDeleted($environment, $actor));
        });
    }

    public function switch(string $applicationIdentifier, string $environmentIdentifier, User $actor): ApplicationEnvironment
    {
        return DB::transaction(function () use ($applicationIdentifier, $environmentIdentifier, $actor): ApplicationEnvironment {
            $application = $this->resolveApplication($applicationIdentifier);
            $environment = $this->environmentRepository->findForApplication($application->id, $environmentIdentifier);

            if (($environment->status?->value ?? $environment->status) === ApplicationEnvironmentStatus::Inactive->value) {
                throw new ApiException('Cannot switch to an inactive environment.', 422);
            }

            $this->environmentRepository->clearCurrentForApplication($application->id, $environment->id);
            $switched = $this->environmentRepository->updateEnvironment($environment, [
                'is_current' => true,
                'updated_by' => $actor->id,
            ]);

            event(new ApplicationEnvironmentSwitched($switched, $actor));

            return $switched;
        });
    }

    /**
     * @return array{environment: ApplicationEnvironment, check: array<string, mixed>}
     */
    public function checkHealth(string $applicationIdentifier, string $environmentIdentifier, User $actor): array
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $environment = $this->environmentRepository->findForApplication($application->id, $environmentIdentifier);

        if (blank($environment->api_url)) {
            throw new ApiException('API URL is required before running a health check.', 422);
        }

        $started = microtime(true);
        $check = [
            'url' => $environment->api_url,
            'success' => false,
            'status_code' => null,
            'latency_ms' => null,
            'message' => null,
            'health_status' => ApplicationEnvironmentHealthStatus::Unknown->value,
        ];

        try {
            $response = Http::timeout(8)
                ->withHeaders(['Accept' => 'application/json', 'User-Agent' => 'AMS-Environment-HealthCheck/1.0'])
                ->get($environment->api_url);

            $latency = (int) round((microtime(true) - $started) * 1000);
            $statusCode = $response->status();
            $success = $response->successful();

            $health = match (true) {
                $success && $latency <= 2000 => ApplicationEnvironmentHealthStatus::Healthy,
                $success => ApplicationEnvironmentHealthStatus::Degraded,
                default => ApplicationEnvironmentHealthStatus::Unhealthy,
            };

            $check = [
                'url' => $environment->api_url,
                'success' => $success,
                'status_code' => $statusCode,
                'latency_ms' => $latency,
                'message' => $success ? 'Health check completed successfully.' : 'Endpoint returned an error response.',
                'health_status' => $health->value,
            ];
        } catch (Throwable $exception) {
            $check = [
                'url' => $environment->api_url,
                'success' => false,
                'status_code' => null,
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'message' => $exception->getMessage(),
                'health_status' => ApplicationEnvironmentHealthStatus::Unhealthy->value,
            ];
        }

        $updated = $this->environmentRepository->updateEnvironment($environment, [
            'health_status' => $check['health_status'],
            'last_health_check' => now(),
            'updated_by' => $actor->id,
        ]);

        event(new ApplicationEnvironmentHealthChecked($updated, $actor, $check));

        return [
            'environment' => $updated,
            'check' => $check,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'name',
            'slug',
            'type',
            'api_url',
            'web_url',
            'status',
            'health_status',
            'is_current',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['slug', 'api_url', 'web_url'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        if (array_key_exists('is_current', $payload)) {
            $payload['is_current'] = (bool) $payload['is_current'];
        }

        if ($isUpdate && array_key_exists('slug', $payload) && $payload['slug'] === null) {
            unset($payload['slug']);
        }

        return $payload;
    }

    /**
     * @param  mixed  $variables
     * @return array<string, string>
     */
    protected function normalizeVariables(mixed $variables): array
    {
        if (! is_array($variables)) {
            return [];
        }

        $normalized = [];

        // Support [{key, value}] and {KEY: value}
        $isList = array_is_list($variables);
        if ($isList) {
            foreach ($variables as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $key = trim((string) ($item['key'] ?? ''));
                if ($key === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                    continue;
                }
                if (array_key_exists('value', $item) && $item['value'] !== null) {
                    $normalized[$key] = (string) $item['value'];
                }
            }

            return $normalized;
        }

        foreach ($variables as $key => $value) {
            $key = trim((string) $key);
            if ($key === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }
            if ($value === null) {
                continue;
            }
            $normalized[$key] = (string) $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>  $existing
     * @param  mixed  $incoming
     * @return array<string, string>
     */
    protected function mergeVariables(array $existing, mixed $incoming): array
    {
        if ($incoming === null) {
            return [];
        }

        if (! is_array($incoming)) {
            return $existing;
        }

        // Allow full replace with list payloads that include explicit keys.
        $merged = $existing;
        $isList = array_is_list($incoming);

        if ($isList) {
            $next = [];
            foreach ($incoming as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $key = trim((string) ($item['key'] ?? ''));
                if ($key === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                    continue;
                }

                $value = $item['value'] ?? null;
                $keepExisting = (bool) ($item['keep_existing'] ?? false)
                    || $value === null
                    || $value === ''
                    || $value === '********';

                if ($keepExisting) {
                    if (array_key_exists($key, $existing)) {
                        $next[$key] = $existing[$key];
                    }
                    continue;
                }

                $next[$key] = (string) $value;
            }

            return $next;
        }

        foreach ($incoming as $key => $value) {
            $key = trim((string) $key);
            if ($key === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }
            if ($value === null || $value === '' || $value === '********') {
                continue;
            }
            $merged[$key] = (string) $value;
        }

        return $merged;
    }

    protected function resolveUniqueSlug(
        int $applicationId,
        ?string $slug,
        string $name,
        string $type,
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($slug ?: ($name ?: $type));
        if ($base === '') {
            $base = $type ?: 'environment';
        }

        $candidate = $base;
        $suffix = 2;

        while ($this->environmentRepository->slugExists($applicationId, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
