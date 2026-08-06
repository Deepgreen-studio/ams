<?php

namespace App\Domains\Compliance\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Compliance\Enums\PrivacyIdentityVerificationStatus;
use App\Domains\Compliance\Enums\PrivacyRequestDecision;
use App\Domains\Compliance\Enums\PrivacyRequestLogAction;
use App\Domains\Compliance\Enums\PrivacyRequestStatus;
use App\Domains\Compliance\Enums\PrivacyRequestType;
use App\Domains\Compliance\Events\PrivacyRequestApproved;
use App\Domains\Compliance\Events\PrivacyRequestAssigned;
use App\Domains\Compliance\Events\PrivacyRequestCompleted;
use App\Domains\Compliance\Events\PrivacyRequestCreated;
use App\Domains\Compliance\Events\PrivacyRequestDataDeleted;
use App\Domains\Compliance\Events\PrivacyRequestExportGenerated;
use App\Domains\Compliance\Events\PrivacyRequestIdentityVerified;
use App\Domains\Compliance\Events\PrivacyRequestRejected;
use App\Domains\Compliance\Events\PrivacyRequestStatusChanged;
use App\Domains\Compliance\Events\PrivacyRequestUpdated;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Compliance\Repositories\PrivacyRequestLogRepository;
use App\Domains\Compliance\Repositories\PrivacyRequestRepository;
use App\Domains\Customers\Repositories\CustomerRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivacyRequestService
{
    public function __construct(
        private readonly PrivacyRequestRepository $privacyRequestRepository,
        private readonly PrivacyRequestLogRepository $privacyRequestLogRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly CustomerRepository $customerRepository
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
            'statistics' => $this->privacyRequestRepository->statistics($companyId),
            'recent_active' => $this->privacyRequestRepository->recentActive($companyId),
            'awaiting_verification' => $this->privacyRequestRepository->awaitingVerification($companyId),
        ];
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
            $assignee = $this->resolveUser($assigneeIdentifier);
            $filters['assigned_to'] = $assignee?->id;
        }

        return $this->privacyRequestRepository->paginateFiltered($filters);
    }

    public function find(string $identifier, bool $withTrashed = false): PrivacyRequest
    {
        return $this->privacyRequestRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): PrivacyRequest
    {
        return $this->find($identifier)->load([
            'company:id,uuid,company_name,status',
            'customer:id,uuid,first_name,last_name,company_name,email,phone',
            'assignee:id,uuid,full_name,email',
            'identityVerifier:id,uuid,full_name,email',
            'decisionMaker:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
            'supportTicket:id,uuid,ticket_number,subject,status',
        ]);
    }

    /**
     * @return Collection<int, \App\Domains\Compliance\Models\PrivacyRequestLog>
     */
    public function timeline(string $identifier): Collection
    {
        $request = $this->find($identifier);

        return $this->privacyRequestLogRepository->forRequest($request->id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): PrivacyRequest
    {
        return DB::transaction(function () use ($data, $actor): PrivacyRequest {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $customer = $this->resolveCustomer($data['customer_id'] ?? null, $company->id);
            $assignee = $this->resolveUser($data['assigned_to'] ?? null);

            $payload = $this->preparePayload($data);
            $payload['company_id'] = $company->id;
            $payload['customer_id'] = $customer?->id;
            $payload['assigned_to'] = $assignee?->id;
            $payload['request_number'] = $this->privacyRequestRepository->generateRequestNumber();
            $payload['status'] = $payload['status'] ?? PrivacyRequestStatus::Submitted->value;
            $payload['identity_verification_status'] = $payload['identity_verification_status']
                ?? PrivacyIdentityVerificationStatus::Pending->value;
            $payload['due_date'] = $payload['due_date'] ?? now()->addDays(30)->toDateString();
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            if ($customer) {
                $payload['requester_name'] = ($payload['requester_name'] ?? null) ?: $customer->display_name;
                $payload['requester_email'] = ($payload['requester_email'] ?? null) ?: $customer->email;
                $payload['requester_phone'] = ($payload['requester_phone'] ?? null) ?: $customer->phone;
            }

            $request = $this->privacyRequestRepository->createRequest($payload);

            $this->privacyRequestLogRepository->recordForRequest(
                $request,
                PrivacyRequestLogAction::Created->value,
                null,
                $request->status?->value ?? (string) $request->status,
                $actor->id,
                'Privacy request created'
            );

            event(new PrivacyRequestCreated($request, $actor));

            if ($request->assigned_to !== null) {
                $this->privacyRequestLogRepository->recordForRequest(
                    $request,
                    PrivacyRequestLogAction::Assigned->value,
                    null,
                    $request->status?->value,
                    $actor->id,
                    'Assigned to officer',
                    ['assigned_to' => $request->assigned_to]
                );
                event(new PrivacyRequestAssigned($request, $actor));
            }

            return $request;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): PrivacyRequest
    {
        return DB::transaction(function () use ($identifier, $data, $actor): PrivacyRequest {
            $request = $this->privacyRequestRepository->findByIdentifierOrFail($identifier);
            $previousAssigneeId = $request->assigned_to;
            $previousStatus = $request->status;

            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('assigned_to', $data)) {
                $assignee = $this->resolveUser($data['assigned_to']);
                $payload['assigned_to'] = $assignee?->id;
            }

            if (array_key_exists('customer_id', $data)) {
                $customer = $this->resolveCustomer($data['customer_id'], $request->company_id);
                $payload['customer_id'] = $customer?->id;
            }

            if (array_key_exists('status', $payload)) {
                $target = PrivacyRequestStatus::tryFrom((string) $payload['status']);
                if ($target === null) {
                    throw new ApiException('Invalid privacy request status.', 422);
                }
                $this->assertCanTransition($previousStatus, $target);
                $payload['completed_at'] = $this->resolveCompletedAt($target, $request->completed_at);
            }

            $updated = $this->privacyRequestRepository->updateRequest($request, $payload);

            $this->privacyRequestLogRepository->recordForRequest(
                $updated,
                PrivacyRequestLogAction::Updated->value,
                $previousStatus?->value,
                $updated->status?->value,
                $actor->id,
                'Privacy request updated'
            );

            if (
                array_key_exists('status', $payload)
                && ($updated->status?->value ?? null) !== ($previousStatus?->value ?? null)
            ) {
                $this->privacyRequestLogRepository->recordForRequest(
                    $updated,
                    PrivacyRequestLogAction::StatusChanged->value,
                    $previousStatus?->value,
                    $updated->status?->value,
                    $actor->id,
                    'Status changed'
                );
                event(new PrivacyRequestStatusChanged($updated, $actor, $previousStatus?->value));
            }

            event(new PrivacyRequestUpdated($updated, $actor));

            if (
                array_key_exists('assigned_to', $payload)
                && $payload['assigned_to'] !== null
                && (int) $payload['assigned_to'] !== (int) $previousAssigneeId
            ) {
                $this->privacyRequestLogRepository->recordForRequest(
                    $updated,
                    PrivacyRequestLogAction::Assigned->value,
                    $updated->status?->value,
                    $updated->status?->value,
                    $actor->id,
                    'Assigned to officer',
                    ['assigned_to' => $payload['assigned_to']]
                );
                event(new PrivacyRequestAssigned($updated, $actor));
            }

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function verifyIdentity(string $identifier, array $data, User $actor): PrivacyRequest
    {
        return DB::transaction(function () use ($identifier, $data, $actor): PrivacyRequest {
            $request = $this->privacyRequestRepository->findByIdentifierOrFail($identifier);
            $verified = (bool) ($data['verified'] ?? false);
            $notes = $data['notes'] ?? null;
            $fromStatus = $request->status;

            $payload = [
                'identity_verification_status' => $verified
                    ? PrivacyIdentityVerificationStatus::Verified->value
                    : PrivacyIdentityVerificationStatus::Failed->value,
                'identity_verified_at' => $verified ? now() : null,
                'identity_verified_by' => $actor->id,
                'identity_verification_notes' => $notes,
                'updated_by' => $actor->id,
            ];

            if ($verified) {
                $target = PrivacyRequestStatus::UnderReview;
                if ($fromStatus !== null && $fromStatus->canTransitionTo($target)) {
                    $payload['status'] = $target->value;
                } elseif ($fromStatus === PrivacyRequestStatus::Submitted) {
                    $payload['status'] = PrivacyRequestStatus::UnderReview->value;
                }
            } else {
                $payload['status'] = PrivacyRequestStatus::IdentityPending->value;
            }

            $updated = $this->privacyRequestRepository->updateRequest($request, $payload);

            $this->privacyRequestLogRepository->recordForRequest(
                $updated,
                $verified
                    ? PrivacyRequestLogAction::IdentityVerified->value
                    : PrivacyRequestLogAction::IdentityFailed->value,
                $fromStatus?->value,
                $updated->status?->value,
                $actor->id,
                $notes ?? ($verified ? 'Identity verified' : 'Identity verification failed')
            );

            event(new PrivacyRequestIdentityVerified($updated, $actor, $verified));

            if (($updated->status?->value ?? null) !== ($fromStatus?->value ?? null)) {
                event(new PrivacyRequestStatusChanged($updated, $actor, $fromStatus?->value));
            }

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function approve(string $identifier, array $data, User $actor): PrivacyRequest
    {
        return DB::transaction(function () use ($identifier, $data, $actor): PrivacyRequest {
            $request = $this->privacyRequestRepository->findByIdentifierOrFail($identifier);
            $this->assertIdentityVerified($request);

            $fromStatus = $request->status;
            $target = PrivacyRequestStatus::Approved;
            $this->assertCanTransition($fromStatus, $target);

            $decision = PrivacyRequestDecision::tryFrom((string) ($data['decision'] ?? 'approved'))
                ?? PrivacyRequestDecision::Approved;

            $updated = $this->privacyRequestRepository->updateRequest($request, [
                'status' => $target->value,
                'decision' => $decision->value,
                'decision_notes' => $data['notes'] ?? null,
                'decision_at' => now(),
                'decision_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->privacyRequestLogRepository->recordForRequest(
                $updated,
                PrivacyRequestLogAction::Approved->value,
                $fromStatus?->value,
                $target->value,
                $actor->id,
                $data['notes'] ?? 'Request approved',
                ['decision' => $decision->value]
            );

            event(new PrivacyRequestApproved($updated, $actor));
            event(new PrivacyRequestStatusChanged($updated, $actor, $fromStatus?->value));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reject(string $identifier, array $data, User $actor): PrivacyRequest
    {
        return DB::transaction(function () use ($identifier, $data, $actor): PrivacyRequest {
            $request = $this->privacyRequestRepository->findByIdentifierOrFail($identifier);
            $fromStatus = $request->status;
            $target = PrivacyRequestStatus::Rejected;
            $this->assertCanTransition($fromStatus, $target);

            $updated = $this->privacyRequestRepository->updateRequest($request, [
                'status' => $target->value,
                'decision' => PrivacyRequestDecision::Rejected->value,
                'decision_notes' => $data['notes'] ?? null,
                'decision_at' => now(),
                'decision_by' => $actor->id,
                'completed_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->privacyRequestLogRepository->recordForRequest(
                $updated,
                PrivacyRequestLogAction::Rejected->value,
                $fromStatus?->value,
                $target->value,
                $actor->id,
                $data['notes'] ?? 'Request rejected'
            );

            event(new PrivacyRequestRejected($updated, $actor));
            event(new PrivacyRequestStatusChanged($updated, $actor, $fromStatus?->value));

            return $updated;
        });
    }

    public function generateExport(string $identifier, User $actor): PrivacyRequest
    {
        return DB::transaction(function () use ($identifier, $actor): PrivacyRequest {
            $request = $this->privacyRequestRepository->findByIdentifierOrFail($identifier);
            $type = $request->request_type;

            if (! $type instanceof PrivacyRequestType || ! $type->requiresExport()) {
                throw new ApiException('This request type does not support data export.', 422);
            }

            $this->assertApprovedOrInProgress($request);

            $exportPayload = $this->buildExportPayload($request);
            $relativePath = 'privacy-exports/'.$request->uuid.'/'.Str::slug($request->request_number).'-export.json';
            Storage::disk('local')->put($relativePath, json_encode($exportPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $fromStatus = $request->status;
            $payload = [
                'export_payload' => $exportPayload,
                'export_file_path' => $relativePath,
                'export_generated_at' => now(),
                'updated_by' => $actor->id,
            ];

            if ($fromStatus === PrivacyRequestStatus::Approved) {
                $payload['status'] = PrivacyRequestStatus::InProgress->value;
            }

            $updated = $this->privacyRequestRepository->updateRequest($request, $payload);

            $this->privacyRequestLogRepository->recordForRequest(
                $updated,
                PrivacyRequestLogAction::ExportGenerated->value,
                $fromStatus?->value,
                $updated->status?->value,
                $actor->id,
                'Subject data export generated',
                ['export_file_path' => $relativePath]
            );

            event(new PrivacyRequestExportGenerated($updated, $actor));

            if (($updated->status?->value ?? null) !== ($fromStatus?->value ?? null)) {
                event(new PrivacyRequestStatusChanged($updated, $actor, $fromStatus?->value));
            }

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function confirmDeletion(string $identifier, array $data, User $actor): PrivacyRequest
    {
        return DB::transaction(function () use ($identifier, $data, $actor): PrivacyRequest {
            $request = $this->privacyRequestRepository->findByIdentifierOrFail($identifier);
            $type = $request->request_type;

            if (! $type instanceof PrivacyRequestType || ! $type->requiresDeletion()) {
                throw new ApiException('This request type does not support data deletion confirmation.', 422);
            }

            $this->assertApprovedOrInProgress($request);

            if (! ($data['confirmed'] ?? false)) {
                throw new ApiException('Deletion confirmation is required.', 422);
            }

            $fromStatus = $request->status;
            $payload = [
                'deletion_confirmed_at' => now(),
                'updated_by' => $actor->id,
            ];

            if ($fromStatus === PrivacyRequestStatus::Approved) {
                $payload['status'] = PrivacyRequestStatus::InProgress->value;
            }

            $updated = $this->privacyRequestRepository->updateRequest($request, $payload);

            $this->privacyRequestLogRepository->recordForRequest(
                $updated,
                PrivacyRequestLogAction::DataDeleted->value,
                $fromStatus?->value,
                $updated->status?->value,
                $actor->id,
                $data['notes'] ?? 'Data deletion confirmed',
                ['confirmed' => true]
            );

            event(new PrivacyRequestDataDeleted($updated, $actor));

            if (($updated->status?->value ?? null) !== ($fromStatus?->value ?? null)) {
                event(new PrivacyRequestStatusChanged($updated, $actor, $fromStatus?->value));
            }

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(string $identifier, array $data, User $actor): PrivacyRequest
    {
        return DB::transaction(function () use ($identifier, $data, $actor): PrivacyRequest {
            $request = $this->privacyRequestRepository->findByIdentifierOrFail($identifier);
            $fromStatus = $request->status;
            $target = PrivacyRequestStatus::Completed;
            $this->assertCanTransition($fromStatus, $target);

            $type = $request->request_type;
            if ($type instanceof PrivacyRequestType && $type->requiresExport() && blank($request->export_generated_at)) {
                throw new ApiException('Generate the data export before completing this request.', 422);
            }
            if ($type instanceof PrivacyRequestType && $type->requiresDeletion() && blank($request->deletion_confirmed_at)) {
                throw new ApiException('Confirm data deletion before completing this request.', 422);
            }

            $updated = $this->privacyRequestRepository->updateRequest($request, [
                'status' => $target->value,
                'completed_at' => now(),
                'decision_notes' => $data['notes'] ?? $request->decision_notes,
                'updated_by' => $actor->id,
            ]);

            $this->privacyRequestLogRepository->recordForRequest(
                $updated,
                PrivacyRequestLogAction::Completed->value,
                $fromStatus?->value,
                $target->value,
                $actor->id,
                $data['notes'] ?? 'Privacy request completed'
            );

            event(new PrivacyRequestCompleted($updated, $actor));
            event(new PrivacyRequestStatusChanged($updated, $actor, $fromStatus?->value));

            return $updated;
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $request = $this->privacyRequestRepository->findByIdentifierOrFail($identifier);
            $this->privacyRequestRepository->updateRequest($request, ['updated_by' => $actor->id]);
            $request->delete();

            $this->privacyRequestLogRepository->recordForRequest(
                $request,
                PrivacyRequestLogAction::Cancelled->value,
                $request->status?->value,
                $request->status?->value,
                $actor->id,
                'Privacy request soft deleted'
            );
        });
    }

    public function restore(string $identifier, User $actor): PrivacyRequest
    {
        return DB::transaction(function () use ($identifier, $actor): PrivacyRequest {
            $request = $this->privacyRequestRepository->findByIdentifierOrFail($identifier, withTrashed: true);

            if (! $request->trashed()) {
                throw new ApiException('Privacy request is not deleted.', 422);
            }

            $request->restore();
            $restored = $this->privacyRequestRepository->updateRequest($request, ['updated_by' => $actor->id]);

            $this->privacyRequestLogRepository->recordForRequest(
                $restored,
                PrivacyRequestLogAction::Restored->value,
                null,
                $restored->status?->value,
                $actor->id,
                'Privacy request restored'
            );

            return $restored;
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildExportPayload(PrivacyRequest $request): array
    {
        $customer = $request->customer;

        return [
            'request_number' => $request->request_number,
            'request_type' => $request->request_type?->value ?? $request->request_type,
            'generated_at' => now()->toIso8601String(),
            'subject' => [
                'name' => $request->requester_name,
                'email' => $request->requester_email,
                'phone' => $request->requester_phone,
                'customer_uuid' => $customer?->uuid,
            ],
            'customer_profile' => $customer ? [
                'uuid' => $customer->uuid,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'company_name' => $customer->company_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'customer_type' => $customer->customer_type?->value ?? $customer->customer_type,
                'created_at' => optional($customer->created_at)?->toIso8601String(),
            ] : null,
            'company' => [
                'uuid' => $request->company?->uuid,
                'company_name' => $request->company?->company_name,
            ],
            'notes' => 'Enterprise GDPR subject data package generated by AMS Compliance Center.',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePayload(array $data, bool $isUpdate = false): array
    {
        $allowed = [
            'request_type',
            'requester_name',
            'requester_email',
            'requester_phone',
            'description',
            'status',
            'due_date',
            'identity_verification_status',
            'support_ticket_id',
        ];

        $payload = array_intersect_key($data, array_flip($allowed));

        foreach (['requester_phone', 'description', 'due_date'] as $nullable) {
            if (array_key_exists($nullable, $payload) && blank($payload[$nullable])) {
                $payload[$nullable] = null;
            }
        }

        if (array_key_exists('support_ticket_id', $payload) && blank($payload['support_ticket_id'])) {
            $payload['support_ticket_id'] = null;
        }

        return $payload;
    }

    protected function resolveCustomer(mixed $identifier, int $companyId): ?\App\Domains\Customers\Models\Customer
    {
        if (blank($identifier)) {
            return null;
        }

        $customer = $this->customerRepository->findByIdentifierOrFail((string) $identifier);

        if ((int) $customer->company_id !== $companyId) {
            throw new ApiException('Customer does not belong to the selected company.', 422);
        }

        return $customer;
    }

    protected function resolveUser(mixed $identifier): ?User
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
            throw new ApiException('User not found.', 422);
        }

        return $user;
    }

    protected function assertCanTransition(?PrivacyRequestStatus $from, PrivacyRequestStatus $to): void
    {
        if ($from === null) {
            return;
        }

        if ($from === $to) {
            return;
        }

        if (! $from->canTransitionTo($to)) {
            throw new ApiException(
                sprintf('Cannot transition privacy request from %s to %s.', $from->value, $to->value),
                422
            );
        }
    }

    protected function assertIdentityVerified(PrivacyRequest $request): void
    {
        $status = $request->identity_verification_status;

        if (! $status instanceof PrivacyIdentityVerificationStatus || ! $status->isVerified()) {
            throw new ApiException('Identity must be verified before approval.', 422);
        }
    }

    protected function assertApprovedOrInProgress(PrivacyRequest $request): void
    {
        $status = $request->status;

        if (! in_array($status, [PrivacyRequestStatus::Approved, PrivacyRequestStatus::InProgress], true)) {
            throw new ApiException('Request must be approved before this action.', 422);
        }
    }

    protected function resolveCompletedAt(?PrivacyRequestStatus $status, mixed $existing): ?string
    {
        if ($status === null) {
            return $existing instanceof \DateTimeInterface
                ? $existing->format('Y-m-d H:i:s')
                : ($existing !== null ? (string) $existing : null);
        }

        if ($status->isCompleted() || $status === PrivacyRequestStatus::Rejected) {
            if ($existing instanceof \DateTimeInterface) {
                return $existing->format('Y-m-d H:i:s');
            }
            if (! blank($existing)) {
                return (string) $existing;
            }

            return now()->format('Y-m-d H:i:s');
        }

        return null;
    }
}
