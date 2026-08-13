<?php

namespace App\Domains\Compliance\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Compliance\Enums\ComplianceCasePriority;
use App\Domains\Compliance\Enums\ComplianceCaseStatus;
use App\Domains\Compliance\Events\ComplianceCaseAssigned;
use App\Domains\Compliance\Events\ComplianceCaseCreated;
use App\Domains\Compliance\Events\ComplianceCaseDeleted;
use App\Domains\Compliance\Events\ComplianceCaseRestored;
use App\Domains\Compliance\Events\ComplianceCaseUpdated;
use App\Domains\Compliance\Models\ComplianceCase;
use App\Domains\Compliance\Repositories\ComplianceCaseRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ComplianceCaseService
{
    public function __construct(
        private readonly ComplianceCaseRepository $complianceCaseRepository,
        private readonly CompanyRepository $companyRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $companyIdentifier = null): array
    {
        $companyId = null;

        if (! blank($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail($companyIdentifier);
            $companyId = $company->id;
        }

        return [
            'statistics' => $this->complianceCaseRepository->statistics($companyId),
            'recent_active' => $this->complianceCaseRepository->recentActive($companyId),
            'elevated' => $this->complianceCaseRepository->elevatedPriority($companyId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function statistics(?string $companyIdentifier = null): array
    {
        $companyId = null;

        if (! blank($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail($companyIdentifier);
            $companyId = $company->id;
        }

        return $this->complianceCaseRepository->statistics($companyId);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $companyIdentifier = $filters['company'] ?? $filters['company_id'] ?? null;
        if (! empty($companyIdentifier) && ! is_numeric($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier);
            $filters['company_id'] = $company->id;
        }

        $assigneeIdentifier = $filters['assigned_to'] ?? $filters['assignee'] ?? null;
        if (! empty($assigneeIdentifier) && ! is_numeric($assigneeIdentifier)) {
            $assignee = $this->resolveAssignee($assigneeIdentifier);
            $filters['assigned_to'] = $assignee?->id;
        }

        return $this->complianceCaseRepository->paginateFiltered($filters);
    }

    public function find(string $identifier, bool $withTrashed = false): ComplianceCase
    {
        return $this->complianceCaseRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): ComplianceCase
    {
        $case = $this->find($identifier);

        return $case->load([
            'company:id,uuid,company_name,status',
            'assignee:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ComplianceCase
    {
        return DB::transaction(function () use ($data, $actor): ComplianceCase {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $assignee = $this->resolveAssignee($data['assigned_to'] ?? null);

            $payload = $this->preparePayload($data);
            $payload['company_id'] = $company->id;
            $payload['assigned_to'] = $assignee?->id;
            $payload['case_number'] = $this->complianceCaseRepository->generateCaseNumber();
            $payload['status'] = $payload['status'] ?? ComplianceCaseStatus::Open->value;
            $payload['priority'] = $payload['priority'] ?? ComplianceCasePriority::Medium->value;
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;
            $payload['completed_at'] = $this->resolveCompletedAt(
                ComplianceCaseStatus::tryFrom((string) $payload['status']),
                null
            );

            $case = $this->complianceCaseRepository->createCase($payload);
            event(new ComplianceCaseCreated($case, $actor));

            if ($case->assigned_to !== null) {
                event(new ComplianceCaseAssigned($case, $actor));
            }

            return $case;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): ComplianceCase
    {
        return DB::transaction(function () use ($identifier, $data, $actor): ComplianceCase {
            $case = $this->complianceCaseRepository->findByIdentifierOrFail($identifier);
            $previousAssigneeId = $case->assigned_to;

            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('assigned_to', $data)) {
                $assignee = $this->resolveAssignee($data['assigned_to']);
                $payload['assigned_to'] = $assignee?->id;
            }

            if (array_key_exists('status', $payload)) {
                $payload['completed_at'] = $this->resolveCompletedAt(
                    ComplianceCaseStatus::tryFrom((string) $payload['status']),
                    $case->completed_at
                );
            }

            $updated = $this->complianceCaseRepository->updateCase($case, $payload);
            event(new ComplianceCaseUpdated($updated, $actor));

            if (
                array_key_exists('assigned_to', $payload)
                && $payload['assigned_to'] !== null
                && (int) $payload['assigned_to'] !== (int) $previousAssigneeId
            ) {
                event(new ComplianceCaseAssigned($updated, $actor));
            }

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $case = $this->complianceCaseRepository->findByIdentifierOrFail($identifier);
            $this->complianceCaseRepository->updateCase($case, ['updated_by' => $actor->id]);
            $case->delete();
            event(new ComplianceCaseDeleted($case, $actor));
        });
    }

    public function restore(string $identifier, User $actor): ComplianceCase
    {
        return DB::transaction(function () use ($identifier, $actor): ComplianceCase {
            $case = $this->complianceCaseRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $case->trashed()) {
                throw new ApiException('Compliance case is not deleted.', 422);
            }

            $case->restore();
            $restored = $this->complianceCaseRepository->updateCase($case, ['updated_by' => $actor->id]);
            event(new ComplianceCaseRestored($restored, $actor));

            return $restored;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'title',
            'description',
            'case_type',
            'priority',
            'status',
            'due_date',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['description', 'due_date'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        if ($isUpdate && empty($payload) && ! array_key_exists('assigned_to', $data)) {
            return $payload;
        }

        return $payload;
    }

    protected function resolveAssignee(mixed $identifier): ?User
    {
        if (blank($identifier)) {
            return null;
        }

        /** @var User|null $user */
        $user = User::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('uuid', $identifier);
                if (ctype_digit((string) $identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        if (! $user) {
            throw new ApiException('Assignee not found.', 422);
        }

        return $user;
    }

    protected function resolveCompletedAt(?ComplianceCaseStatus $status, mixed $existingCompletedAt): ?string
    {
        if ($status === null) {
            return $existingCompletedAt instanceof \DateTimeInterface
                ? $existingCompletedAt->format('Y-m-d H:i:s')
                : ($existingCompletedAt !== null ? (string) $existingCompletedAt : null);
        }

        if ($status->isCompleted()) {
            if ($existingCompletedAt instanceof \DateTimeInterface) {
                return $existingCompletedAt->format('Y-m-d H:i:s');
            }

            if (! blank($existingCompletedAt)) {
                return (string) $existingCompletedAt;
            }

            return now()->format('Y-m-d H:i:s');
        }

        return null;
    }
}
