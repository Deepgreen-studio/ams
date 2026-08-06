<?php

namespace App\Domains\Compliance\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Compliance\Enums\PolicyApprovalStatus;
use App\Domains\Compliance\Enums\PolicyDocumentStatus;
use App\Domains\Compliance\Events\PolicyApproved;
use App\Domains\Compliance\Events\PolicyCreated;
use App\Domains\Compliance\Events\PolicyPublished;
use App\Domains\Compliance\Events\PolicyRejected;
use App\Domains\Compliance\Events\PolicySubmittedForReview;
use App\Domains\Compliance\Events\PolicyUpdated;
use App\Domains\Compliance\Events\PolicyVersionRestored;
use App\Domains\Compliance\Models\PolicyApproval;
use App\Domains\Compliance\Models\PolicyDocument;
use App\Domains\Compliance\Models\PolicyVersion;
use App\Domains\Compliance\Repositories\PolicyApprovalRepository;
use App\Domains\Compliance\Repositories\PolicyDocumentRepository;
use App\Domains\Compliance\Repositories\PolicyVersionRepository;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Repositories\ContentRepository;
use App\Domains\Content\Repositories\ContentVersionRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PolicyDocumentService
{
    /**
     * @var list<string>
     */
    protected array $snapshotFields = [
        'title',
        'slug',
        'description',
        'body',
        'policy_type',
        'status',
        'effective_at',
        'expires_at',
        'review_due_at',
        'content_id',
        'published_at',
    ];

    public function __construct(
        private readonly PolicyDocumentRepository $policyDocumentRepository,
        private readonly PolicyVersionRepository $policyVersionRepository,
        private readonly PolicyApprovalRepository $policyApprovalRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly ContentRepository $contentRepository,
        private readonly ContentVersionRepository $contentVersionRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $companyIdentifier = null): array
    {
        $companyId = $this->resolveCompanyId($companyIdentifier);

        return [
            'statistics' => $this->policyDocumentRepository->statistics($companyId),
            'recent' => $this->policyDocumentRepository->recent($companyId),
            'approval_queue' => $this->policyApprovalRepository->paginateFiltered([
                'company_id' => $companyId,
                'status' => PolicyApprovalStatus::Pending->value,
                'per_page' => 8,
            ])->getCollection(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->policyDocumentRepository->paginateFiltered(
            $this->normalizeCompanyFilter($filters)
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function approvalQueue(array $filters = []): LengthAwarePaginator
    {
        $filters = $this->normalizeCompanyFilter($filters);
        $filters['status'] = $filters['status'] ?? PolicyApprovalStatus::Pending->value;

        return $this->policyApprovalRepository->paginateFiltered($filters);
    }

    public function find(string $identifier, bool $withTrashed = false): PolicyDocument
    {
        return $this->policyDocumentRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): PolicyDocument
    {
        return $this->find($identifier)->load([
            'company:id,uuid,company_name,status',
            'content:id,uuid,title,slug,version',
            'assignee:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
            'versions.creator:id,uuid,full_name,email',
            'approvals.requester:id,uuid,full_name,email',
            'approvals.reviewer:id,uuid,full_name,email',
            'approvals.version:id,uuid,version,status,title',
        ]);
    }

    /**
     * @return Collection<int, PolicyVersion>
     */
    public function versions(string $identifier): Collection
    {
        $policy = $this->find($identifier);

        return $this->policyVersionRepository->forPolicy($policy->id);
    }

    public function showVersion(string $identifier, string $versionIdentifier): PolicyVersion
    {
        $policy = $this->find($identifier);

        return $this->policyVersionRepository->findForPolicy($policy->id, $versionIdentifier)
            ->load(['creator:id,uuid,full_name,email']);
    }

    /**
     * @return array{from: PolicyVersion, to: PolicyVersion, comparison: array{changes: array<string, array{from: mixed, to: mixed}>, changed_fields: list<string>}}
     */
    public function compare(string $identifier, string $fromIdentifier, string $toIdentifier): array
    {
        $policy = $this->find($identifier);
        $from = $this->policyVersionRepository->findForPolicy($policy->id, $fromIdentifier);
        $to = $this->policyVersionRepository->findForPolicy($policy->id, $toIdentifier);

        if ($from->id === $to->id) {
            throw new ApiException('Select two different versions to compare.', 422);
        }

        $fromSnapshot = is_array($from->snapshot) ? $from->snapshot : [];
        $toSnapshot = is_array($to->snapshot) ? $to->snapshot : [];
        $keys = array_values(array_unique(array_merge(array_keys($fromSnapshot), array_keys($toSnapshot))));
        $changes = [];

        foreach ($keys as $key) {
            $fromValue = $fromSnapshot[$key] ?? null;
            $toValue = $toSnapshot[$key] ?? null;
            if ($this->normalizeComparable($fromValue) !== $this->normalizeComparable($toValue)) {
                $changes[$key] = [
                    'from' => $fromValue,
                    'to' => $toValue,
                ];
            }
        }

        if (($from->status?->value ?? $from->status) !== ($to->status?->value ?? $to->status)) {
            $changes['status'] = [
                'from' => $from->status?->value ?? $from->status,
                'to' => $to->status?->value ?? $to->status,
            ];
        }

        return [
            'from' => $from->load(['creator:id,uuid,full_name,email']),
            'to' => $to->load(['creator:id,uuid,full_name,email']),
            'comparison' => [
                'changes' => $changes,
                'changed_fields' => array_keys($changes),
            ],
        ];
    }

    /**
     * Linked CMS version history when policy is connected to content.
     *
     * @return array<string, mixed>
     */
    public function cmsVersionHistory(string $identifier): array
    {
        $policy = $this->find($identifier)->load('content:id,uuid,title,slug,version');

        if ($policy->content_id === null || ! $policy->content) {
            return [
                'linked' => false,
                'content' => null,
                'versions' => [],
            ];
        }

        $content = $policy->content;
        $versions = $this->contentVersionRepository->versionsForContent($content->id);

        return [
            'linked' => true,
            'content' => [
                'uuid' => $content->uuid,
                'title' => $content->title,
                'slug' => $content->slug,
                'version' => $content->version,
            ],
            'versions' => $versions,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): PolicyDocument
    {
        return DB::transaction(function () use ($data, $actor): PolicyDocument {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $content = $this->resolveContent($data['content_id'] ?? null);
            $assignee = $this->resolveUser($data['assigned_to'] ?? null);

            $payload = $this->preparePayload($data);
            $payload['company_id'] = $company->id;
            $payload['content_id'] = $content?->id;
            $payload['assigned_to'] = $assignee?->id;
            $payload['policy_number'] = $this->policyDocumentRepository->generatePolicyNumber();
            $payload['slug'] = $this->policyDocumentRepository->uniqueSlug(
                $company->id,
                (string) ($payload['slug'] ?? $payload['title'])
            );
            $payload['status'] = $payload['status'] ?? PolicyDocumentStatus::Draft->value;
            $payload['current_version'] = 1;
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $policy = $this->policyDocumentRepository->createPolicy($payload);
            $this->recordVersion($policy, 'Initial version', $actor->id);

            event(new PolicyCreated($policy, $actor));

            return $policy->fresh(['company', 'content', 'assignee', 'creator', 'versions']) ?? $policy;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): PolicyDocument
    {
        return DB::transaction(function () use ($identifier, $data, $actor): PolicyDocument {
            $policy = $this->policyDocumentRepository->findByIdentifierOrFail($identifier);
            $previousStatus = $policy->status;

            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('assigned_to', $data)) {
                $payload['assigned_to'] = $this->resolveUser($data['assigned_to'])?->id;
            }

            if (array_key_exists('content_id', $data)) {
                $payload['content_id'] = $this->resolveContent($data['content_id'])?->id;
            }

            if (array_key_exists('title', $payload) || array_key_exists('slug', $payload)) {
                $titleForSlug = (string) ($payload['slug'] ?? $payload['title'] ?? $policy->title);
                $payload['slug'] = $this->policyDocumentRepository->uniqueSlug(
                    $policy->company_id,
                    $titleForSlug,
                    $policy->id
                );
            }

            if (array_key_exists('status', $payload)) {
                $target = PolicyDocumentStatus::tryFrom((string) $payload['status']);
                if ($target === null) {
                    throw new ApiException('Invalid policy status.', 422);
                }
                $this->assertTransition($previousStatus, $target);
                if ($target === PolicyDocumentStatus::Published) {
                    $payload['published_at'] = $policy->published_at ?? now();
                }
            }

            // Never overwrite history: bump version and snapshot every content update.
            $nextVersion = max(
                $policy->current_version + 1,
                $this->policyVersionRepository->nextVersionNumber($policy->id)
            );
            $payload['current_version'] = $nextVersion;

            $updated = $this->policyDocumentRepository->updatePolicy($policy, $payload);
            $reason = (string) ($data['change_summary'] ?? $data['reason'] ?? 'Policy updated');
            $this->recordVersion($updated, $reason, $actor->id);

            event(new PolicyUpdated($updated, $actor));

            return $updated;
        });
    }

    public function submitForReview(string $identifier, User $actor, array $data = []): PolicyDocument
    {
        return DB::transaction(function () use ($identifier, $actor, $data): PolicyDocument {
            $policy = $this->policyDocumentRepository->findByIdentifierOrFail($identifier);
            $this->assertTransition($policy->status, PolicyDocumentStatus::Review);

            $nextVersion = max(
                $policy->current_version + 1,
                $this->policyVersionRepository->nextVersionNumber($policy->id)
            );

            $updated = $this->policyDocumentRepository->updatePolicy($policy, [
                'status' => PolicyDocumentStatus::Review->value,
                'current_version' => $nextVersion,
                'updated_by' => $actor->id,
            ]);

            $version = $this->recordVersion($updated, 'Submitted for review', $actor->id);

            $this->policyApprovalRepository->cancelPendingForPolicy($updated->id);
            $this->policyApprovalRepository->createApproval([
                'policy_id' => $updated->id,
                'policy_version_id' => $version->id,
                'status' => PolicyApprovalStatus::Pending->value,
                'requested_by' => $actor->id,
                'comments' => $data['comments'] ?? null,
                'requested_at' => now(),
            ]);

            event(new PolicySubmittedForReview($updated, $actor));

            return $updated->fresh([
                'company',
                'content',
                'versions',
                'approvals.requester',
                'approvals.version',
            ]) ?? $updated;
        });
    }

    public function approve(string $approvalIdentifier, User $actor, array $data = []): PolicyDocument
    {
        return DB::transaction(function () use ($approvalIdentifier, $actor, $data): PolicyDocument {
            $approval = $this->policyApprovalRepository->findByIdentifierOrFail($approvalIdentifier);

            if ($approval->status !== PolicyApprovalStatus::Pending) {
                throw new ApiException('Only pending approvals can be decided.', 422);
            }

            $policy = $this->policyDocumentRepository->findByIdentifierOrFail((string) $approval->policy->uuid);
            $this->assertTransition($policy->status, PolicyDocumentStatus::Approved);

            $this->policyApprovalRepository->updateApproval($approval, [
                'status' => PolicyApprovalStatus::Approved->value,
                'reviewed_by' => $actor->id,
                'comments' => $data['comments'] ?? $approval->comments,
                'decided_at' => now(),
            ]);

            $nextVersion = max(
                $policy->current_version + 1,
                $this->policyVersionRepository->nextVersionNumber($policy->id)
            );

            $updated = $this->policyDocumentRepository->updatePolicy($policy, [
                'status' => PolicyDocumentStatus::Approved->value,
                'current_version' => $nextVersion,
                'updated_by' => $actor->id,
            ]);

            $this->recordVersion($updated, 'Policy approved', $actor->id);
            event(new PolicyApproved($updated, $actor));

            return $updated;
        });
    }

    public function reject(string $approvalIdentifier, User $actor, array $data = []): PolicyDocument
    {
        return DB::transaction(function () use ($approvalIdentifier, $actor, $data): PolicyDocument {
            $approval = $this->policyApprovalRepository->findByIdentifierOrFail($approvalIdentifier);

            if ($approval->status !== PolicyApprovalStatus::Pending) {
                throw new ApiException('Only pending approvals can be decided.', 422);
            }

            $policy = $this->policyDocumentRepository->findByIdentifierOrFail((string) $approval->policy->uuid);

            $this->policyApprovalRepository->updateApproval($approval, [
                'status' => PolicyApprovalStatus::Rejected->value,
                'reviewed_by' => $actor->id,
                'comments' => $data['comments'] ?? null,
                'decided_at' => now(),
            ]);

            $nextVersion = max(
                $policy->current_version + 1,
                $this->policyVersionRepository->nextVersionNumber($policy->id)
            );

            $updated = $this->policyDocumentRepository->updatePolicy($policy, [
                'status' => PolicyDocumentStatus::Draft->value,
                'current_version' => $nextVersion,
                'updated_by' => $actor->id,
            ]);

            $this->recordVersion($updated, 'Policy rejected: '.($data['comments'] ?? 'Returned to draft'), $actor->id);
            event(new PolicyRejected($updated, $actor));

            return $updated;
        });
    }

    public function publish(string $identifier, User $actor, array $data = []): PolicyDocument
    {
        return DB::transaction(function () use ($identifier, $actor, $data): PolicyDocument {
            $policy = $this->policyDocumentRepository->findByIdentifierOrFail($identifier);
            $this->assertTransition($policy->status, PolicyDocumentStatus::Published);

            $nextVersion = max(
                $policy->current_version + 1,
                $this->policyVersionRepository->nextVersionNumber($policy->id)
            );

            $updated = $this->policyDocumentRepository->updatePolicy($policy, [
                'status' => PolicyDocumentStatus::Published->value,
                'published_at' => now(),
                'effective_at' => $data['effective_at'] ?? $policy->effective_at ?? now(),
                'current_version' => $nextVersion,
                'updated_by' => $actor->id,
            ]);

            $this->recordVersion($updated, 'Policy published', $actor->id);
            event(new PolicyPublished($updated, $actor));

            return $updated;
        });
    }

    public function restoreVersion(string $identifier, string $versionIdentifier, User $actor, array $data = []): PolicyDocument
    {
        return DB::transaction(function () use ($identifier, $versionIdentifier, $actor, $data): PolicyDocument {
            $policy = $this->policyDocumentRepository->findByIdentifierOrFail($identifier);
            $source = $this->policyVersionRepository->findForPolicy($policy->id, $versionIdentifier);
            $snapshot = is_array($source->snapshot) ? $source->snapshot : [];

            $payload = [];
            foreach ($this->snapshotFields as $field) {
                if (array_key_exists($field, $snapshot)) {
                    $payload[$field] = $snapshot[$field];
                }
            }

            // Restoring never overwrites history — always creates a new version.
            $nextVersion = max(
                $policy->current_version + 1,
                $this->policyVersionRepository->nextVersionNumber($policy->id)
            );

            $payload['current_version'] = $nextVersion;
            $payload['updated_by'] = $actor->id;
            // After restore, return to draft for re-approval unless already published snapshot.
            if (($payload['status'] ?? null) === PolicyDocumentStatus::Published->value) {
                $payload['status'] = PolicyDocumentStatus::Approved->value;
            }

            $updated = $this->policyDocumentRepository->updatePolicy($policy, $payload);
            $reason = (string) ($data['reason'] ?? 'Restored from version '.$source->version);

            $this->policyVersionRepository->recordForPolicy(
                $updated,
                $nextVersion,
                $updated->status?->value ?? (string) $updated->status,
                $reason,
                $actor->id,
                $this->buildSnapshot($updated),
                isRestore: true,
                restoredFrom: $source->version
            );

            event(new PolicyVersionRestored($updated, $actor, $source->version));
            event(new PolicyUpdated($updated, $actor));

            return $updated->fresh(['versions.creator', 'company', 'content']) ?? $updated;
        });
    }

    public function linkCmsContent(string $identifier, string $contentIdentifier, User $actor): PolicyDocument
    {
        return DB::transaction(function () use ($identifier, $contentIdentifier, $actor): PolicyDocument {
            $policy = $this->policyDocumentRepository->findByIdentifierOrFail($identifier);
            $content = $this->contentRepository->findByIdentifierOrFail($contentIdentifier);

            $nextVersion = max(
                $policy->current_version + 1,
                $this->policyVersionRepository->nextVersionNumber($policy->id)
            );

            $updated = $this->policyDocumentRepository->updatePolicy($policy, [
                'content_id' => $content->id,
                'current_version' => $nextVersion,
                'updated_by' => $actor->id,
            ]);

            $this->recordVersion($updated, 'Linked to CMS content '.$content->uuid, $actor->id);
            event(new PolicyUpdated($updated, $actor));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $policy = $this->policyDocumentRepository->findByIdentifierOrFail($identifier);
            $policy->updated_by = $actor->id;
            $policy->save();
            $policy->delete();
        });
    }

    private function recordVersion(PolicyDocument $policy, string $reason, ?int $actorId): PolicyVersion
    {
        return $this->policyVersionRepository->recordForPolicy(
            $policy,
            (int) $policy->current_version,
            $policy->status?->value ?? (string) $policy->status,
            $reason,
            $actorId,
            $this->buildSnapshot($policy)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(PolicyDocument $policy): array
    {
        return [
            'title' => $policy->title,
            'slug' => $policy->slug,
            'description' => $policy->description,
            'body' => $policy->body,
            'policy_type' => $policy->policy_type?->value ?? $policy->policy_type,
            'status' => $policy->status?->value ?? $policy->status,
            'effective_at' => optional($policy->effective_at)?->toIso8601String(),
            'expires_at' => optional($policy->expires_at)?->toIso8601String(),
            'review_due_at' => optional($policy->review_due_at)?->toDateString(),
            'content_id' => $policy->content_id,
            'content_uuid' => $policy->content?->uuid,
            'published_at' => optional($policy->published_at)?->toIso8601String(),
            'current_version' => $policy->current_version,
        ];
    }

    private function normalizeComparable(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function assertTransition(?PolicyDocumentStatus $from, PolicyDocumentStatus $to): void
    {
        if ($from === null || ! $from->canTransitionTo($to)) {
            throw new ApiException(
                'Cannot transition policy from '.($from?->label() ?? 'unknown').' to '.$to->label().'.',
                422
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(array $data, bool $isUpdate = false): array
    {
        $keys = [
            'title', 'slug', 'policy_type', 'description', 'body', 'status',
            'effective_at', 'expires_at', 'review_due_at', 'published_at',
        ];

        $payload = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeCompanyFilter(array $filters): array
    {
        $companyIdentifier = $filters['company'] ?? $filters['company_id'] ?? null;
        if (! empty($companyIdentifier) && ! is_numeric($companyIdentifier)) {
            $filters['company_id'] = $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier)->id;
        }

        $assignee = $filters['assigned_to'] ?? $filters['assignee'] ?? null;
        if (! empty($assignee) && ! is_numeric($assignee)) {
            $filters['assigned_to'] = $this->resolveUser($assignee)?->id;
        }

        return $filters;
    }

    private function resolveCompanyId(?string $companyIdentifier): ?int
    {
        if (blank($companyIdentifier)) {
            return null;
        }

        return $this->companyRepository->findByIdentifierOrFail($companyIdentifier)->id;
    }

    private function resolveContent(mixed $identifier): ?Content
    {
        if (blank($identifier)) {
            return null;
        }

        return $this->contentRepository->findByIdentifierOrFail((string) $identifier);
    }

    private function resolveUser(mixed $identifier): ?User
    {
        if (blank($identifier)) {
            return null;
        }

        if ($identifier instanceof User) {
            return $identifier;
        }

        if (is_numeric($identifier)) {
            return User::query()->find((int) $identifier);
        }

        return User::query()->where('uuid', (string) $identifier)->first();
    }
}
