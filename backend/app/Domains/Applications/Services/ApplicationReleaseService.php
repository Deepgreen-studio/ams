<?php

namespace App\Domains\Applications\Services;

use App\Domains\Applications\Enums\ApplicationReleaseApprovalStatus;
use App\Domains\Applications\Enums\ApplicationReleaseRollbackStatus;
use App\Domains\Applications\Enums\ApplicationReleaseStatus;
use App\Domains\Applications\Enums\ApplicationReleaseType;
use App\Domains\Applications\Events\ApplicationReleaseApproved;
use App\Domains\Applications\Events\ApplicationReleaseCreated;
use App\Domains\Applications\Events\ApplicationReleaseDeleted;
use App\Domains\Applications\Events\ApplicationReleaseDeployed;
use App\Domains\Applications\Events\ApplicationReleaseRejected;
use App\Domains\Applications\Events\ApplicationReleaseRolledBack;
use App\Domains\Applications\Events\ApplicationReleaseUpdated;
use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationRelease;
use App\Domains\Applications\Repositories\ApplicationEnvironmentRepository;
use App\Domains\Applications\Repositories\ApplicationReleaseRepository;
use App\Domains\Applications\Repositories\ApplicationRepository;
use App\Domains\Applications\Repositories\ApplicationVersionRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationReleaseService
{
    public function __construct(
        private readonly ApplicationReleaseRepository $releaseRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly ApplicationVersionRepository $versionRepository,
        private readonly ApplicationEnvironmentRepository $environmentRepository,
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
        $filters = $this->resolveEnvironmentFilter($application->id, $filters);

        return $this->releaseRepository->paginateForApplication($application->id, $filters);
    }

    /**
     * @return array{application: Application, releases: Collection<int, ApplicationRelease>, summary: array<string, int>}
     */
    public function dashboard(string $applicationIdentifier): array
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return [
            'application' => $application,
            'releases' => $this->releaseRepository->dashboardForApplication($application->id),
            'summary' => $this->releaseRepository->summaryForApplication($application->id),
        ];
    }

    /**
     * @return Collection<int, ApplicationRelease>
     */
    public function calendar(string $applicationIdentifier, ?string $from = null, ?string $to = null): Collection
    {
        $application = $this->resolveApplication($applicationIdentifier);
        $start = $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
        $end = $to ? Carbon::parse($to)->endOfDay() : now()->endOfMonth();

        if ($end->lt($start)) {
            throw new ApiException('Calendar end date must be after start date.', 422);
        }

        return $this->releaseRepository->calendarForApplication($application->id, $start, $end);
    }

    /**
     * @return Collection<int, ApplicationRelease>
     */
    public function timeline(string $applicationIdentifier, int $limit = 40): Collection
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->releaseRepository->timelineForApplication($application->id, $limit);
    }

    public function find(string $applicationIdentifier, string $releaseIdentifier): ApplicationRelease
    {
        $application = $this->resolveApplication($applicationIdentifier);

        return $this->releaseRepository->findForApplication($application->id, $releaseIdentifier)
            ->load([
                'version:id,uuid,version_number,status,build_number,release_notes',
                'environment:id,uuid,name,slug,type',
                'notes',
                'approver:id,uuid,full_name,email',
                'rolledBackBy:id,uuid,full_name,email',
                'creator:id,uuid,full_name,email',
                'updater:id,uuid,full_name,email',
                'rollbackOf:id,uuid,name,version_label,status',
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(string $applicationIdentifier, array $data, User $actor): ApplicationRelease
    {
        return DB::transaction(function () use ($applicationIdentifier, $data, $actor): ApplicationRelease {
            $application = $this->resolveApplication($applicationIdentifier);
            $version = $this->versionRepository->findForApplication(
                $application->id,
                (string) $data['application_version_id']
            );
            $environmentId = $this->resolveEnvironmentId($application->id, $data['environment_id'] ?? null);

            $requiresApproval = (bool) ($data['requires_approval'] ?? true);
            $status = ApplicationReleaseStatus::Planned->value;
            $approvalStatus = $requiresApproval
                ? ApplicationReleaseApprovalStatus::Pending->value
                : ApplicationReleaseApprovalStatus::NotRequired->value;

            if (! empty($data['scheduled_at'])) {
                $status = ApplicationReleaseStatus::Scheduled->value;
            }

            $release = $this->releaseRepository->createRelease([
                'application_id' => $application->id,
                'application_version_id' => $version->id,
                'environment_id' => $environmentId,
                'name' => $data['name'],
                'version_label' => $version->version_number,
                'release_type' => $data['release_type'] ?? ApplicationReleaseType::Minor->value,
                'status' => $data['status'] ?? $status,
                'approval_status' => $data['approval_status'] ?? $approvalStatus,
                'rollback_status' => ApplicationReleaseRollbackStatus::None->value,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'deployment_date' => $data['deployment_date'] ?? null,
                'plan_summary' => $data['plan_summary'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->syncNotes($release, $data['notes'] ?? [], $actor);

            $release = $this->find($applicationIdentifier, $release->uuid);
            event(new ApplicationReleaseCreated($release, $actor));

            return $release;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $applicationIdentifier, string $releaseIdentifier, array $data, User $actor): ApplicationRelease
    {
        return DB::transaction(function () use ($applicationIdentifier, $releaseIdentifier, $data, $actor): ApplicationRelease {
            $release = $this->find($applicationIdentifier, $releaseIdentifier);
            $this->assertEditable($release);

            $payload = [
                'updated_by' => $actor->id,
            ];

            if (array_key_exists('name', $data)) {
                $payload['name'] = $data['name'];
            }
            if (array_key_exists('plan_summary', $data)) {
                $payload['plan_summary'] = $data['plan_summary'];
            }
            if (array_key_exists('release_type', $data)) {
                $payload['release_type'] = $data['release_type'];
            }
            if (array_key_exists('metadata', $data)) {
                $payload['metadata'] = $data['metadata'];
            }
            if (array_key_exists('deployment_date', $data)) {
                $payload['deployment_date'] = $data['deployment_date'];
            }
            if (array_key_exists('scheduled_at', $data)) {
                $payload['scheduled_at'] = $data['scheduled_at'];
                if ($data['scheduled_at'] && $release->status === ApplicationReleaseStatus::Planned) {
                    $payload['status'] = ApplicationReleaseStatus::Scheduled->value;
                }
            }
            if (array_key_exists('environment_id', $data)) {
                $payload['environment_id'] = $this->resolveEnvironmentId($release->application_id, $data['environment_id']);
            }
            if (array_key_exists('application_version_id', $data) && $data['application_version_id']) {
                $version = $this->versionRepository->findForApplication(
                    $release->application_id,
                    (string) $data['application_version_id']
                );
                $payload['application_version_id'] = $version->id;
                $payload['version_label'] = $version->version_number;
            }
            if (array_key_exists('status', $data) && $data['status'] === ApplicationReleaseStatus::Cancelled->value) {
                $payload['status'] = ApplicationReleaseStatus::Cancelled->value;
            }

            $updated = $this->releaseRepository->updateRelease($release, $payload);

            if (array_key_exists('notes', $data)) {
                $this->syncNotes($updated, is_array($data['notes']) ? $data['notes'] : [], $actor);
            }

            $updated = $this->find($applicationIdentifier, $updated->uuid);
            event(new ApplicationReleaseUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $applicationIdentifier, string $releaseIdentifier, User $actor): void
    {
        DB::transaction(function () use ($applicationIdentifier, $releaseIdentifier, $actor): void {
            $release = $this->find($applicationIdentifier, $releaseIdentifier);
            $this->assertEditable($release);
            $release->delete();
            event(new ApplicationReleaseDeleted($release, $actor));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(string $applicationIdentifier, string $releaseIdentifier, array $data, User $actor): ApplicationRelease
    {
        return DB::transaction(function () use ($applicationIdentifier, $releaseIdentifier, $data, $actor): ApplicationRelease {
            $release = $this->find($applicationIdentifier, $releaseIdentifier);
            $this->assertNotTerminal($release);

            if (in_array($release->status, [
                ApplicationReleaseStatus::Deploying,
                ApplicationReleaseStatus::Deployed,
            ], true)) {
                throw new ApiException('Cannot reschedule a release that is deploying or already deployed.', 422);
            }

            $updated = $this->releaseRepository->updateRelease($release, [
                'scheduled_at' => $data['scheduled_at'],
                'deployment_date' => $data['deployment_date'] ?? $release->deployment_date,
                'status' => ApplicationReleaseStatus::Scheduled->value,
                'updated_by' => $actor->id,
            ]);

            event(new ApplicationReleaseUpdated($updated, $actor));

            return $this->find($applicationIdentifier, $updated->uuid);
        });
    }

    public function submitApproval(string $applicationIdentifier, string $releaseIdentifier, User $actor): ApplicationRelease
    {
        return DB::transaction(function () use ($applicationIdentifier, $releaseIdentifier, $actor): ApplicationRelease {
            $release = $this->find($applicationIdentifier, $releaseIdentifier);
            $this->assertNotTerminal($release);

            if ($release->approval_status === ApplicationReleaseApprovalStatus::Approved) {
                throw new ApiException('Release is already approved.', 422);
            }

            $updated = $this->releaseRepository->updateRelease($release, [
                'approval_status' => ApplicationReleaseApprovalStatus::Pending->value,
                'status' => ApplicationReleaseStatus::PendingApproval->value,
                'approved_by' => null,
                'approved_at' => null,
                'approval_notes' => null,
                'updated_by' => $actor->id,
            ]);

            event(new ApplicationReleaseUpdated($updated, $actor));

            return $this->find($applicationIdentifier, $updated->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function approve(string $applicationIdentifier, string $releaseIdentifier, array $data, User $actor): ApplicationRelease
    {
        return DB::transaction(function () use ($applicationIdentifier, $releaseIdentifier, $data, $actor): ApplicationRelease {
            $release = $this->find($applicationIdentifier, $releaseIdentifier);
            $this->assertNotTerminal($release);

            if ($release->approval_status !== ApplicationReleaseApprovalStatus::Pending
                && $release->status !== ApplicationReleaseStatus::PendingApproval) {
                throw new ApiException('Release is not awaiting approval.', 422);
            }

            $updated = $this->releaseRepository->updateRelease($release, [
                'approval_status' => ApplicationReleaseApprovalStatus::Approved->value,
                'status' => ApplicationReleaseStatus::Approved->value,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'approval_notes' => $data['approval_notes'] ?? $release->approval_notes,
                'updated_by' => $actor->id,
            ]);

            event(new ApplicationReleaseApproved($updated, $actor));

            return $this->find($applicationIdentifier, $updated->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reject(string $applicationIdentifier, string $releaseIdentifier, array $data, User $actor): ApplicationRelease
    {
        return DB::transaction(function () use ($applicationIdentifier, $releaseIdentifier, $data, $actor): ApplicationRelease {
            $release = $this->find($applicationIdentifier, $releaseIdentifier);
            $this->assertNotTerminal($release);

            if ($release->approval_status !== ApplicationReleaseApprovalStatus::Pending
                && $release->status !== ApplicationReleaseStatus::PendingApproval) {
                throw new ApiException('Release is not awaiting approval.', 422);
            }

            $updated = $this->releaseRepository->updateRelease($release, [
                'approval_status' => ApplicationReleaseApprovalStatus::Rejected->value,
                'status' => ApplicationReleaseStatus::Rejected->value,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'approval_notes' => $data['approval_notes'] ?? null,
                'updated_by' => $actor->id,
            ]);

            event(new ApplicationReleaseRejected($updated, $actor));

            return $this->find($applicationIdentifier, $updated->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function deploy(string $applicationIdentifier, string $releaseIdentifier, array $data, User $actor): ApplicationRelease
    {
        return DB::transaction(function () use ($applicationIdentifier, $releaseIdentifier, $data, $actor): ApplicationRelease {
            $release = $this->find($applicationIdentifier, $releaseIdentifier);
            $this->assertNotTerminal($release);

            $allowed = in_array($release->approval_status, [
                ApplicationReleaseApprovalStatus::Approved,
                ApplicationReleaseApprovalStatus::NotRequired,
            ], true);

            if (! $allowed) {
                throw new ApiException('Release must be approved (or approval not required) before deployment.', 422);
            }

            $failed = (bool) ($data['failed'] ?? false);
            $deploymentDate = $data['deployment_date'] ?? now();

            $updated = $this->releaseRepository->updateRelease($release, [
                'status' => $failed
                    ? ApplicationReleaseStatus::Failed->value
                    : ApplicationReleaseStatus::Deployed->value,
                'deployment_date' => $deploymentDate,
                'deployed_at' => $failed ? null : now(),
                'updated_by' => $actor->id,
            ]);

            if (! $failed) {
                event(new ApplicationReleaseDeployed($updated, $actor));
            } else {
                event(new ApplicationReleaseUpdated($updated, $actor));
            }

            return $this->find($applicationIdentifier, $updated->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function rollback(string $applicationIdentifier, string $releaseIdentifier, array $data, User $actor): ApplicationRelease
    {
        return DB::transaction(function () use ($applicationIdentifier, $releaseIdentifier, $data, $actor): ApplicationRelease {
            $release = $this->find($applicationIdentifier, $releaseIdentifier);

            if ($release->status !== ApplicationReleaseStatus::Deployed) {
                throw new ApiException('Only deployed releases can be rolled back.', 422);
            }

            if ($release->rollback_status === ApplicationReleaseRollbackStatus::Completed) {
                throw new ApiException('Release has already been rolled back.', 422);
            }

            $updated = $this->releaseRepository->updateRelease($release, [
                'status' => ApplicationReleaseStatus::RolledBack->value,
                'rollback_status' => ApplicationReleaseRollbackStatus::Completed->value,
                'rolled_back_by' => $actor->id,
                'rolled_back_at' => now(),
                'updated_by' => $actor->id,
                'metadata' => array_merge(is_array($release->metadata) ? $release->metadata : [], [
                    'rollback_reason' => $data['reason'] ?? null,
                ]),
            ]);

            if (! empty($data['create_rollback_release'])) {
                $rollbackName = $data['rollback_release_name'] ?? 'Rollback of '.$release->name;
                $rollback = $this->releaseRepository->createRelease([
                    'application_id' => $release->application_id,
                    'application_version_id' => $release->application_version_id,
                    'environment_id' => $release->environment_id,
                    'name' => $rollbackName,
                    'version_label' => $release->version_label,
                    'release_type' => ApplicationReleaseType::Rollback->value,
                    'status' => ApplicationReleaseStatus::Deployed->value,
                    'approval_status' => ApplicationReleaseApprovalStatus::NotRequired->value,
                    'rollback_status' => ApplicationReleaseRollbackStatus::None->value,
                    'scheduled_at' => now(),
                    'deployment_date' => now(),
                    'deployed_at' => now(),
                    'rollback_of_release_id' => $release->id,
                    'plan_summary' => $data['reason'] ?? 'Rollback release',
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
                event(new ApplicationReleaseCreated($rollback, $actor));
            }

            event(new ApplicationReleaseRolledBack($updated, $actor));

            return $this->find($applicationIdentifier, $updated->uuid);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $notes
     */
    protected function syncNotes(ApplicationRelease $release, array $notes, User $actor): void
    {
        $this->releaseRepository->deleteNotesForRelease($release->id);

        foreach (array_values($notes) as $index => $note) {
            if (empty($note['title'])) {
                continue;
            }

            $this->releaseRepository->createNote([
                'release_id' => $release->id,
                'locale' => $note['locale'] ?? 'en',
                'title' => $note['title'],
                'content' => $note['content'] ?? null,
                'audience' => $note['audience'] ?? 'public',
                'sort_order' => $note['sort_order'] ?? $index,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        }
    }

    protected function assertEditable(ApplicationRelease $release): void
    {
        if (in_array($release->status, [
            ApplicationReleaseStatus::Deployed,
            ApplicationReleaseStatus::RolledBack,
        ], true)) {
            throw new ApiException('Deployed or rolled-back releases cannot be edited. Create a new release instead.', 422);
        }
    }

    protected function assertNotTerminal(ApplicationRelease $release): void
    {
        $status = $release->status instanceof ApplicationReleaseStatus
            ? $release->status
            : ApplicationReleaseStatus::tryFrom((string) $release->status);

        if ($status?->isTerminal()) {
            throw new ApiException('This release is in a terminal state and cannot be changed.', 422);
        }
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
        $identifier = $filters['environment'] ?? $filters['environment_id'] ?? null;
        if ($identifier === null || $identifier === '') {
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
