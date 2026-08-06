<?php

namespace App\Domains\Applications\Services;

use App\Domains\Applications\Enums\ApplicationVersionStatus;
use App\Domains\Applications\Events\ApplicationVersionCreated;
use App\Domains\Applications\Events\ApplicationVersionDeleted;
use App\Domains\Applications\Events\ApplicationVersionUpdated;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationVersion;
use App\Domains\Applications\Repositories\ApplicationRepository;
use App\Domains\Applications\Repositories\ApplicationVersionRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationVersionService
{
    public function __construct(
        private readonly ApplicationVersionRepository $versionRepository,
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

        return $this->versionRepository->paginateForApplication($application->id, $filters);
    }

    public function find(string $applicationIdentifier, string $versionIdentifier): ApplicationVersion
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->versionRepository
            ->findForApplication($application->id, $versionIdentifier)
            ->load([
                'application:id,uuid,name,slug,current_version,minimum_supported_version',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(string $applicationIdentifier, array $data, User $actor): ApplicationVersion
    {
        return DB::transaction(function () use ($applicationIdentifier, $data, $actor): ApplicationVersion {
            $application = $this->resolveApplication($applicationIdentifier);
            $payload = $this->preparePayload($data);
            $semver = $this->resolveSemver($data, $payload);
            $payload = array_merge($payload, $semver);

            if ($this->versionRepository->versionNumberExists($application->id, $payload['version_number'])) {
                throw new ApiException('Version number already exists for this application.', 422);
            }

            $payload['application_id'] = $application->id;
            $payload['status'] = $payload['status'] ?? ApplicationVersionStatus::Draft->value;
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $version = $this->versionRepository->createVersion($payload);
            $this->syncProductionState($application, $version, $actor);
            event(new ApplicationVersionCreated($version, $actor));

            return $version->fresh(['application', 'creator', 'updater']) ?? $version;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $applicationIdentifier, string $versionIdentifier, array $data, User $actor): ApplicationVersion
    {
        return DB::transaction(function () use ($applicationIdentifier, $versionIdentifier, $data, $actor): ApplicationVersion {
            $application = $this->resolveApplication($applicationIdentifier);
            $version = $this->versionRepository->findForApplication($application->id, $versionIdentifier);
            $payload = $this->preparePayload($data, isUpdate: true);

            if ($this->requiresSemverResolution($data, $payload)) {
                $semver = $this->resolveSemver($data, array_merge([
                    'version_number' => $version->version_number,
                    'major' => $version->major,
                    'minor' => $version->minor,
                    'patch' => $version->patch,
                ], $payload));
                $payload = array_merge($payload, $semver);

                if ($this->versionRepository->versionNumberExists($application->id, $payload['version_number'], $version->id)) {
                    throw new ApiException('Version number already exists for this application.', 422);
                }
            }

            $payload['updated_by'] = $actor->id;
            $updated = $this->versionRepository->updateVersion($version, $payload);
            $this->syncProductionState($application, $updated, $actor);
            event(new ApplicationVersionUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $applicationIdentifier, string $versionIdentifier, User $actor): void
    {
        DB::transaction(function () use ($applicationIdentifier, $versionIdentifier, $actor): void {
            $application = $this->resolveApplication($applicationIdentifier);
            $version = $this->versionRepository->findForApplication($application->id, $versionIdentifier);
            $this->versionRepository->updateVersion($version, ['updated_by' => $actor->id]);
            $version->delete();
            event(new ApplicationVersionDeleted($version, $actor));
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function compare(string $applicationIdentifier, string $fromIdentifier, string $toIdentifier): array
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $from = $this->versionRepository->findForApplication($application->id, $fromIdentifier);
        $to = $this->versionRepository->findForApplication($application->id, $toIdentifier);

        $semverResult = $this->compareSemver($from, $to);
        $fields = [
            'version_number',
            'build_number',
            'status',
            'release_date',
            'minimum_supported_version',
            'release_notes',
            'major',
            'minor',
            'patch',
        ];

        $changes = [];
        foreach ($fields as $field) {
            $fromValue = $this->normalizeCompareValue($from->{$field});
            $toValue = $this->normalizeCompareValue($to->{$field});
            if ($fromValue !== $toValue) {
                $changes[$field] = [
                    'from' => $fromValue,
                    'to' => $toValue,
                ];
            }
        }

        return [
            'from' => $from,
            'to' => $to,
            'comparison' => [
                'result' => $semverResult['result'],
                'semver_diff' => $semverResult['diff'],
                'changes' => $changes,
            ],
        ];
    }

    /**
     * @return Collection<int, ApplicationVersion>
     */
    public function timeline(string $applicationIdentifier): Collection
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->versionRepository->timelineForApplication($application->id);
    }

    /**
     * @return Collection<int, ApplicationVersion>
     */
    public function history(string $applicationIdentifier): Collection
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->versionRepository->historyForApplication($application->id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'version_number',
            'major',
            'minor',
            'patch',
            'build_number',
            'status',
            'release_date',
            'minimum_supported_version',
            'release_notes',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['build_number', 'minimum_supported_version', 'release_notes', 'release_date'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        if (array_key_exists('status', $payload) && is_string($payload['status'])) {
            $payload['status'] = strtolower($payload['status']);
        }

        if ($isUpdate) {
            // Keep empty version_number from wiping on partial update.
            if (array_key_exists('version_number', $payload) && blank($payload['version_number'])) {
                unset($payload['version_number']);
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     */
    protected function requiresSemverResolution(array $data, array $payload): bool
    {
        return array_key_exists('version_number', $data)
            || array_key_exists('major', $data)
            || array_key_exists('minor', $data)
            || array_key_exists('patch', $data)
            || array_key_exists('version_number', $payload)
            || array_key_exists('major', $payload)
            || array_key_exists('minor', $payload)
            || array_key_exists('patch', $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     * @return array{version_number: string, major: int, minor: int, patch: int}
     */
    protected function resolveSemver(array $data, array $payload): array
    {
        if (! empty($payload['version_number']) || ! empty($data['version_number'])) {
            $raw = (string) ($payload['version_number'] ?? $data['version_number']);
            $parts = $this->parseSemver($raw);

            return [
                'version_number' => $parts['version_number'],
                'major' => $parts['major'],
                'minor' => $parts['minor'],
                'patch' => $parts['patch'],
            ];
        }

        if (
            array_key_exists('major', $payload)
            || array_key_exists('minor', $payload)
            || array_key_exists('patch', $payload)
            || array_key_exists('major', $data)
            || array_key_exists('minor', $data)
            || array_key_exists('patch', $data)
        ) {
            $major = (int) ($payload['major'] ?? $data['major'] ?? 0);
            $minor = (int) ($payload['minor'] ?? $data['minor'] ?? 0);
            $patch = (int) ($payload['patch'] ?? $data['patch'] ?? 0);

            if ($major < 0 || $minor < 0 || $patch < 0) {
                throw new ApiException('Semantic version parts must be non-negative integers.', 422);
            }

            return [
                'version_number' => "{$major}.{$minor}.{$patch}",
                'major' => $major,
                'minor' => $minor,
                'patch' => $patch,
            ];
        }

        throw new ApiException('A semantic version number is required.', 422);
    }

    /**
     * @return array{version_number: string, major: int, minor: int, patch: int}
     */
    protected function parseSemver(string $value): array
    {
        $normalized = ltrim(trim($value), 'vV');

        if (! preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/', $normalized, $matches)) {
            throw new ApiException('Version number must follow semantic versioning (MAJOR.MINOR.PATCH).', 422);
        }

        return [
            'version_number' => $normalized,
            'major' => (int) $matches[1],
            'minor' => (int) $matches[2],
            'patch' => (int) $matches[3],
        ];
    }

    protected function syncProductionState(Application $application, ApplicationVersion $version, User $actor): void
    {
        $status = $version->status instanceof ApplicationVersionStatus
            ? $version->status
            : ApplicationVersionStatus::tryFrom((string) $version->status);

        if ($status !== ApplicationVersionStatus::Production) {
            return;
        }

        $this->versionRepository->demoteOtherProductionVersions($application->id, $version->id);

        $application->fill([
            'current_version' => $version->version_number,
            'minimum_supported_version' => $version->minimum_supported_version ?: $application->minimum_supported_version,
            'updated_by' => $actor->id,
        ]);
        $application->save();
    }

    /**
     * @return array{result: string, diff: array{major: int, minor: int, patch: int}}
     */
    protected function compareSemver(ApplicationVersion $from, ApplicationVersion $to): array
    {
        $diff = [
            'major' => $to->major - $from->major,
            'minor' => $to->minor - $from->minor,
            'patch' => $to->patch - $from->patch,
        ];

        $cmp = [$to->major <=> $from->major, $to->minor <=> $from->minor, $to->patch <=> $from->patch];
        $result = 'same';
        foreach ($cmp as $value) {
            if ($value === 1) {
                $result = 'upgrade';
                break;
            }
            if ($value === -1) {
                $result = 'downgrade';
                break;
            }
        }

        return [
            'result' => $result,
            'diff' => $diff,
        ];
    }

    protected function normalizeCompareValue(mixed $value): mixed
    {
        if ($value instanceof ApplicationVersionStatus) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return $value;
    }
}
