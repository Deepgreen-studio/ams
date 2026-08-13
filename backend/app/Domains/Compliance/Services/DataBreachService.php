<?php

namespace App\Domains\Compliance\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Compliance\Enums\BreachActionStatus;
use App\Domains\Compliance\Enums\BreachActionType;
use App\Domains\Compliance\Enums\BreachNotificationStatus;
use App\Domains\Compliance\Enums\BreachNotificationType;
use App\Domains\Compliance\Enums\DataBreachSeverity;
use App\Domains\Compliance\Enums\DataBreachStatus;
use App\Domains\Compliance\Events\DataBreachActionRecorded;
use App\Domains\Compliance\Events\DataBreachAssigned;
use App\Domains\Compliance\Events\DataBreachClosed;
use App\Domains\Compliance\Events\DataBreachContained;
use App\Domains\Compliance\Events\DataBreachCreated;
use App\Domains\Compliance\Events\DataBreachDeleted;
use App\Domains\Compliance\Events\DataBreachNotificationSent;
use App\Domains\Compliance\Events\DataBreachRecovered;
use App\Domains\Compliance\Events\DataBreachRestored;
use App\Domains\Compliance\Events\DataBreachRiskAssessed;
use App\Domains\Compliance\Events\DataBreachStatusChanged;
use App\Domains\Compliance\Events\DataBreachUpdated;
use App\Domains\Compliance\Models\BreachAction;
use App\Domains\Compliance\Models\BreachNotification;
use App\Domains\Compliance\Models\DataBreach;
use App\Domains\Compliance\Repositories\BreachActionRepository;
use App\Domains\Compliance\Repositories\BreachNotificationRepository;
use App\Domains\Compliance\Repositories\DataBreachRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DataBreachService
{
    public function __construct(
        private readonly DataBreachRepository $dataBreachRepository,
        private readonly BreachActionRepository $breachActionRepository,
        private readonly BreachNotificationRepository $breachNotificationRepository,
        private readonly CompanyRepository $companyRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(?string $companyIdentifier = null): array
    {
        $companyId = $this->resolveCompanyId($companyIdentifier);

        return [
            'statistics' => $this->dataBreachRepository->statistics($companyId),
            'recent_active' => $this->dataBreachRepository->recentActive($companyId),
            'regulator_queue' => $this->dataBreachRepository->regulatorQueue($companyId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function statistics(?string $companyIdentifier = null): array
    {
        return $this->dataBreachRepository->statistics($this->resolveCompanyId($companyIdentifier));
    }

    /**
     * @return array<string, mixed>
     */
    public function riskMatrix(?string $companyIdentifier = null): array
    {
        return $this->dataBreachRepository->riskMatrix($this->resolveCompanyId($companyIdentifier));
    }

    /**
     * @return array<string, mixed>
     */
    public function reports(?string $companyIdentifier = null): array
    {
        return $this->dataBreachRepository->reportSummary($this->resolveCompanyId($companyIdentifier));
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

        return $this->dataBreachRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function notificationCenter(array $filters = []): LengthAwarePaginator
    {
        $companyIdentifier = $filters['company'] ?? $filters['company_id'] ?? null;
        if (! empty($companyIdentifier) && ! is_numeric($companyIdentifier)) {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $companyIdentifier);
            $filters['company_id'] = $company->id;
        }

        return $this->breachNotificationRepository->paginateFiltered($filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function notificationStatistics(?string $companyIdentifier = null): array
    {
        $companyId = $this->resolveCompanyId($companyIdentifier);

        return $this->breachNotificationRepository->statistics($companyId);
    }

    public function find(string $identifier, bool $withTrashed = false): DataBreach
    {
        return $this->dataBreachRepository->findByIdentifierOrFail($identifier, $withTrashed);
    }

    public function show(string $identifier): DataBreach
    {
        return $this->find($identifier)->load([
            'company:id,uuid,company_name,status',
            'assignee:id,uuid,full_name,email',
            'riskAssessor:id,uuid,full_name,email',
            'creator:id,uuid,full_name,email',
            'updater:id,uuid,full_name,email',
            'actions.performer:id,uuid,full_name,email',
            'notifications.sender:id,uuid,full_name,email',
        ]);
    }

    /**
     * @return Collection<int, BreachAction>
     */
    public function timeline(string $identifier): Collection
    {
        $breach = $this->find($identifier);

        return $this->breachActionRepository->forBreach($breach->id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): DataBreach
    {
        return DB::transaction(function () use ($data, $actor): DataBreach {
            $company = $this->companyRepository->findByIdentifierOrFail((string) $data['company_id']);
            $assignee = $this->resolveUser($data['assigned_to'] ?? null);

            $payload = $this->preparePayload($data);
            $payload['company_id'] = $company->id;
            $payload['assigned_to'] = $assignee?->id;
            $payload['breach_number'] = $this->dataBreachRepository->generateBreachNumber();
            $payload['status'] = $payload['status'] ?? DataBreachStatus::Reported->value;
            $payload['severity'] = $payload['severity'] ?? DataBreachSeverity::Medium->value;
            $payload['discovered_at'] = $payload['discovered_at'] ?? now();
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            $affectedUsers = $payload['affected_users'] ?? [];
            if (is_array($affectedUsers) && $affectedUsers !== []) {
                $payload['affected_user_count'] = count($affectedUsers);
            }

            $payload = $this->applyNotificationFlags($payload);

            $breach = $this->dataBreachRepository->createBreach($payload);

            $this->breachActionRepository->recordTimeline(
                $breach,
                BreachActionType::StatusChange->value,
                'Incident reported',
                'Data breach incident reported',
                null,
                $breach->status?->value,
                $actor->id
            );

            event(new DataBreachCreated($breach, $actor));

            if ($breach->assigned_to !== null) {
                event(new DataBreachAssigned($breach, $actor));
            }

            return $breach;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): DataBreach
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DataBreach {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);
            $previousAssigneeId = $breach->assigned_to;
            $previousStatus = $breach->status;

            $payload = $this->preparePayload($data, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if (array_key_exists('assigned_to', $data)) {
                $assignee = $this->resolveUser($data['assigned_to']);
                $payload['assigned_to'] = $assignee?->id;
            }

            if (array_key_exists('affected_users', $payload) && is_array($payload['affected_users'])) {
                $payload['affected_user_count'] = count($payload['affected_users']);
            }

            if (array_key_exists('status', $payload)) {
                $target = DataBreachStatus::tryFrom((string) $payload['status']);
                if ($target === null) {
                    throw new ApiException('Invalid data breach status.', 422);
                }
                $this->assertCanTransition($previousStatus, $target);
                $payload['closed_at'] = $target === DataBreachStatus::Closed ? ($breach->closed_at ?? now()) : $breach->closed_at;
                if ($target !== DataBreachStatus::Closed) {
                    $payload['closed_at'] = null;
                }
            }

            $payload = array_merge(
                $payload,
                $this->notificationFlagUpdates($breach, $payload)
            );

            $updated = $this->dataBreachRepository->updateBreach($breach, $payload);

            if (($payload['assigned_to'] ?? $previousAssigneeId) !== $previousAssigneeId) {
                $this->breachActionRepository->recordTimeline(
                    $updated,
                    BreachActionType::Other->value,
                    'Assignment updated',
                    null,
                    null,
                    null,
                    $actor->id,
                    ['assigned_to' => $updated->assigned_to]
                );
                event(new DataBreachAssigned($updated, $actor));
            }

            if (isset($payload['status']) && $previousStatus?->value !== $updated->status?->value) {
                $this->breachActionRepository->recordTimeline(
                    $updated,
                    BreachActionType::StatusChange->value,
                    'Status changed',
                    null,
                    $previousStatus?->value,
                    $updated->status?->value,
                    $actor->id
                );
                event(new DataBreachStatusChanged($updated, $actor, $previousStatus?->value));
            }

            event(new DataBreachUpdated($updated, $actor));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assessRisk(string $identifier, array $data, User $actor): DataBreach
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DataBreach {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);
            $previousStatus = $breach->status;

            $likelihood = (int) $data['risk_likelihood'];
            $impact = (int) $data['risk_impact'];
            $score = $likelihood * $impact;
            $level = DataBreachSeverity::fromRiskScore($score);

            $payload = [
                'risk_likelihood' => $likelihood,
                'risk_impact' => $impact,
                'risk_score' => $score,
                'risk_level' => $level->value,
                'risk_assessment_notes' => $data['risk_assessment_notes'] ?? null,
                'impact_analysis' => $data['impact_analysis'] ?? $breach->impact_analysis,
                'risk_assessed_at' => now(),
                'risk_assessed_by' => $actor->id,
                'severity' => $data['severity'] ?? $level->value,
                'updated_by' => $actor->id,
            ];

            if ($previousStatus === DataBreachStatus::Reported) {
                $this->assertCanTransition($previousStatus, DataBreachStatus::Assessing);
                $payload['status'] = DataBreachStatus::Assessing->value;
            }

            $payload = array_merge($payload, $this->notificationFlagUpdates($breach, $payload));

            $updated = $this->dataBreachRepository->updateBreach($breach, $payload);

            $this->breachActionRepository->recordTimeline(
                $updated,
                BreachActionType::RiskAssessment->value,
                'Risk assessment completed',
                $payload['risk_assessment_notes'],
                $previousStatus?->value,
                $updated->status?->value,
                $actor->id,
                [
                    'risk_likelihood' => $likelihood,
                    'risk_impact' => $impact,
                    'risk_score' => $score,
                    'risk_level' => $level->value,
                ]
            );

            if (($data['impact_analysis'] ?? null) !== null) {
                $this->breachActionRepository->recordTimeline(
                    $updated,
                    BreachActionType::ImpactAnalysis->value,
                    'Impact analysis recorded',
                    (string) $data['impact_analysis'],
                    null,
                    null,
                    $actor->id
                );
            }

            event(new DataBreachRiskAssessed($updated, $actor));

            if ($previousStatus?->value !== $updated->status?->value) {
                event(new DataBreachStatusChanged($updated, $actor, $previousStatus?->value));
            }

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function contain(string $identifier, array $data, User $actor): DataBreach
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DataBreach {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);
            $previousStatus = $breach->status;
            $this->assertCanTransition($previousStatus, DataBreachStatus::Contained);

            $updated = $this->dataBreachRepository->updateBreach($breach, [
                'status' => DataBreachStatus::Contained->value,
                'containment_summary' => $data['containment_summary'],
                'contained_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->breachActionRepository->recordTimeline(
                $updated,
                BreachActionType::Containment->value,
                'Containment completed',
                $data['containment_summary'],
                $previousStatus?->value,
                DataBreachStatus::Contained->value,
                $actor->id
            );

            event(new DataBreachContained($updated, $actor));
            event(new DataBreachStatusChanged($updated, $actor, $previousStatus?->value));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recover(string $identifier, array $data, User $actor): DataBreach
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DataBreach {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);
            $previousStatus = $breach->status;
            $this->assertCanTransition($previousStatus, DataBreachStatus::Recovering);

            $updated = $this->dataBreachRepository->updateBreach($breach, [
                'status' => DataBreachStatus::Recovering->value,
                'recovery_summary' => $data['recovery_summary'],
                'recovered_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->breachActionRepository->recordTimeline(
                $updated,
                BreachActionType::Recovery->value,
                'Recovery actions recorded',
                $data['recovery_summary'],
                $previousStatus?->value,
                DataBreachStatus::Recovering->value,
                $actor->id
            );

            event(new DataBreachRecovered($updated, $actor));
            event(new DataBreachStatusChanged($updated, $actor, $previousStatus?->value));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordRootCause(string $identifier, array $data, User $actor): DataBreach
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DataBreach {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);

            $updated = $this->dataBreachRepository->updateBreach($breach, [
                'root_cause' => $data['root_cause'],
                'root_cause_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->breachActionRepository->recordTimeline(
                $updated,
                BreachActionType::RootCause->value,
                'Root cause analysis recorded',
                $data['root_cause'],
                null,
                null,
                $actor->id
            );

            event(new DataBreachUpdated($updated, $actor));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordLessonsLearned(string $identifier, array $data, User $actor): DataBreach
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DataBreach {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);

            $updated = $this->dataBreachRepository->updateBreach($breach, [
                'lessons_learned' => $data['lessons_learned'],
                'lessons_learned_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->breachActionRepository->recordTimeline(
                $updated,
                BreachActionType::LessonsLearned->value,
                'Lessons learned recorded',
                $data['lessons_learned'],
                null,
                null,
                $actor->id
            );

            event(new DataBreachUpdated($updated, $actor));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAffectedUsers(string $identifier, array $data, User $actor): DataBreach
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DataBreach {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);
            $users = $data['affected_users'] ?? [];

            $updated = $this->dataBreachRepository->updateBreach($breach, [
                'affected_users' => $users,
                'affected_user_count' => count($users),
                'affected_data_categories' => $data['affected_data_categories'] ?? $breach->affected_data_categories,
                'updated_by' => $actor->id,
            ]);

            $this->breachActionRepository->recordTimeline(
                $updated,
                BreachActionType::ImpactAnalysis->value,
                'Affected users updated',
                'Affected user list revised',
                null,
                null,
                $actor->id,
                ['affected_user_count' => count($users)]
            );

            event(new DataBreachUpdated($updated, $actor));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function close(string $identifier, array $data, User $actor): DataBreach
    {
        return DB::transaction(function () use ($identifier, $data, $actor): DataBreach {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);
            $previousStatus = $breach->status;
            $this->assertCanTransition($previousStatus, DataBreachStatus::Closed);

            if ($breach->regulator_notification_required && blank($breach->regulator_notified_at)) {
                throw new ApiException('Regulator notification must be completed before closing.', 422);
            }

            $payload = [
                'status' => DataBreachStatus::Closed->value,
                'closed_at' => now(),
                'updated_by' => $actor->id,
            ];

            if (! empty($data['lessons_learned'])) {
                $payload['lessons_learned'] = $data['lessons_learned'];
                $payload['lessons_learned_at'] = now();
            }

            $updated = $this->dataBreachRepository->updateBreach($breach, $payload);

            $this->breachActionRepository->recordTimeline(
                $updated,
                BreachActionType::StatusChange->value,
                'Incident closed',
                $data['comments'] ?? null,
                $previousStatus?->value,
                DataBreachStatus::Closed->value,
                $actor->id
            );

            event(new DataBreachClosed($updated, $actor));
            event(new DataBreachStatusChanged($updated, $actor, $previousStatus?->value));

            return $updated;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addAction(string $identifier, array $data, User $actor): BreachAction
    {
        return DB::transaction(function () use ($identifier, $data, $actor): BreachAction {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);
            $performer = $this->resolveUser($data['performed_by'] ?? $actor->uuid);

            $action = $this->breachActionRepository->createForBreach($breach, [
                'action_type' => $data['action_type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? BreachActionStatus::Planned->value,
                'performed_by' => $performer?->id ?? $actor->id,
                'due_at' => $data['due_at'] ?? null,
                'completed_at' => ($data['status'] ?? null) === BreachActionStatus::Completed->value ? now() : null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->dataBreachRepository->updateBreach($breach, ['updated_by' => $actor->id]);
            event(new DataBreachActionRecorded($breach->fresh(), $action, $actor));

            return $action;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createNotification(string $identifier, array $data, User $actor): BreachNotification
    {
        return DB::transaction(function () use ($identifier, $data, $actor): BreachNotification {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);

            $notification = $this->breachNotificationRepository->createForBreach($breach, [
                'notification_type' => $data['notification_type'],
                'channel' => $data['channel'] ?? 'email',
                'recipient' => $data['recipient'],
                'subject' => $data['subject'] ?? null,
                'message' => $data['message'] ?? null,
                'status' => $data['status'] ?? BreachNotificationStatus::Draft->value,
                'metadata' => $data['metadata'] ?? null,
            ]);

            if (($data['status'] ?? null) === BreachNotificationStatus::Queued->value
                || ($data['send_now'] ?? false) === true) {
                $notification = $this->markNotificationSent($breach, $notification, $actor, $data);
            } else {
                $this->breachActionRepository->recordTimeline(
                    $breach,
                    BreachActionType::Notification->value,
                    'Notification drafted',
                    $notification->subject,
                    null,
                    null,
                    $actor->id,
                    [
                        'notification_uuid' => $notification->uuid,
                        'notification_type' => $notification->notification_type?->value,
                    ]
                );
            }

            $this->dataBreachRepository->updateBreach($breach, ['updated_by' => $actor->id]);

            return $notification->fresh(['sender', 'dataBreach']) ?? $notification;
        });
    }

    public function sendNotification(string $breachIdentifier, string $notificationIdentifier, User $actor, array $data = []): BreachNotification
    {
        return DB::transaction(function () use ($breachIdentifier, $notificationIdentifier, $actor, $data): BreachNotification {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($breachIdentifier);
            $notification = $this->breachNotificationRepository->findByIdentifierOrFail($notificationIdentifier);

            if ($notification->data_breach_id !== $breach->id) {
                throw new ApiException('Notification does not belong to this breach.', 422);
            }

            return $this->markNotificationSent($breach, $notification, $actor, $data);
        });
    }

    public function delete(string $identifier, User $actor): void
    {
        DB::transaction(function () use ($identifier, $actor): void {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier);
            $breach->updated_by = $actor->id;
            $breach->save();
            $breach->delete();
            event(new DataBreachDeleted($breach, $actor));
        });
    }

    public function restore(string $identifier, User $actor): DataBreach
    {
        return DB::transaction(function () use ($identifier, $actor): DataBreach {
            $breach = $this->dataBreachRepository->findByIdentifierOrFail($identifier, withTrashed: true);
            $breach->restore();
            $breach->updated_by = $actor->id;
            $breach->save();
            event(new DataBreachRestored($breach, $actor));

            return $breach->fresh([
                'company',
                'assignee',
                'creator',
                'updater',
            ]) ?? $breach;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function markNotificationSent(
        DataBreach $breach,
        BreachNotification $notification,
        User $actor,
        array $data = []
    ): BreachNotification {
        $previousStatus = $breach->status;

        $notification = $this->breachNotificationRepository->updateNotification($notification, [
            'status' => BreachNotificationStatus::Sent->value,
            'sent_at' => now(),
            'sent_by' => $actor->id,
            'subject' => $data['subject'] ?? $notification->subject,
            'message' => $data['message'] ?? $notification->message,
        ]);

        $breachPayload = ['updated_by' => $actor->id];

        if ($notification->notification_type === BreachNotificationType::Regulator) {
            $breachPayload['regulator_notified_at'] = now();
            if (! empty($data['regulator_reference'])) {
                $breachPayload['regulator_reference'] = $data['regulator_reference'];
            }
        }

        if ($notification->notification_type === BreachNotificationType::Customer
            || $notification->notification_type === BreachNotificationType::AffectedUser) {
            $breachPayload['customer_notified_at'] = now();
        }

        if (in_array($previousStatus, [
            DataBreachStatus::Assessing,
            DataBreachStatus::Contained,
            DataBreachStatus::Recovering,
        ], true) && $previousStatus->canTransitionTo(DataBreachStatus::Notifying)) {
            $breachPayload['status'] = DataBreachStatus::Notifying->value;
        }

        $updated = $this->dataBreachRepository->updateBreach($breach, $breachPayload);

        $this->breachActionRepository->recordTimeline(
            $updated,
            BreachActionType::Notification->value,
            'Notification sent',
            $notification->subject,
            $previousStatus?->value,
            $updated->status?->value,
            $actor->id,
            [
                'notification_uuid' => $notification->uuid,
                'notification_type' => $notification->notification_type?->value,
                'recipient' => $notification->recipient,
            ]
        );

        event(new DataBreachNotificationSent($updated, $notification, $actor));

        if ($previousStatus?->value !== $updated->status?->value) {
            event(new DataBreachStatusChanged($updated, $actor, $previousStatus?->value));
        }

        return $notification->fresh(['sender', 'dataBreach']) ?? $notification;
    }

    private function assertCanTransition(?DataBreachStatus $from, DataBreachStatus $to): void
    {
        if ($from === null) {
            throw new ApiException('Current breach status is invalid.', 422);
        }

        if (! $from->canTransitionTo($to)) {
            throw new ApiException(
                "Cannot transition breach from {$from->label()} to {$to->label()}.",
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
            'title',
            'description',
            'breach_type',
            'status',
            'severity',
            'discovered_at',
            'occurred_at',
            'affected_user_count',
            'affected_users',
            'affected_data_categories',
            'personal_data_involved',
            'special_category_data',
            'risk_likelihood',
            'risk_impact',
            'risk_score',
            'risk_level',
            'risk_assessment_notes',
            'impact_analysis',
            'containment_summary',
            'recovery_summary',
            'root_cause',
            'lessons_learned',
            'regulator_notification_required',
            'regulator_deadline_at',
            'regulator_reference',
            'customer_notification_required',
        ];

        $payload = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        if (! $isUpdate && ! array_key_exists('personal_data_involved', $payload)) {
            $payload['personal_data_involved'] = true;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyNotificationFlags(array $payload): array
    {
        return array_merge($payload, $this->computeNotificationFlags(
            personalData: (bool) ($payload['personal_data_involved'] ?? true),
            specialCategory: (bool) ($payload['special_category_data'] ?? false),
            riskLevel: (string) ($payload['risk_level'] ?? $payload['severity'] ?? DataBreachSeverity::Medium->value),
            discoveredAt: $payload['discovered_at'] ?? now(),
            existingRegulatorRequired: array_key_exists('regulator_notification_required', $payload)
                ? $payload['regulator_notification_required']
                : null,
            existingCustomerRequired: array_key_exists('customer_notification_required', $payload)
                ? $payload['customer_notification_required']
                : null,
            existingDeadline: $payload['regulator_deadline_at'] ?? null,
        ));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function notificationFlagUpdates(DataBreach $breach, array $overrides = []): array
    {
        return $this->computeNotificationFlags(
            personalData: (bool) ($overrides['personal_data_involved'] ?? $breach->personal_data_involved ?? true),
            specialCategory: (bool) ($overrides['special_category_data'] ?? $breach->special_category_data ?? false),
            riskLevel: (string) (
                $overrides['risk_level']
                ?? $overrides['severity']
                ?? $breach->risk_level?->value
                ?? $breach->severity?->value
                ?? DataBreachSeverity::Medium->value
            ),
            discoveredAt: $overrides['discovered_at'] ?? $breach->discovered_at ?? now(),
            existingRegulatorRequired: array_key_exists('regulator_notification_required', $overrides)
                ? $overrides['regulator_notification_required']
                : $breach->regulator_notification_required,
            existingCustomerRequired: array_key_exists('customer_notification_required', $overrides)
                ? $overrides['customer_notification_required']
                : $breach->customer_notification_required,
            existingDeadline: $overrides['regulator_deadline_at'] ?? $breach->regulator_deadline_at,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function computeNotificationFlags(
        bool $personalData,
        bool $specialCategory,
        string $riskLevel,
        mixed $discoveredAt,
        mixed $existingRegulatorRequired,
        mixed $existingCustomerRequired,
        mixed $existingDeadline,
    ): array {
        $highRisk = in_array($riskLevel, [
            DataBreachSeverity::High->value,
            DataBreachSeverity::Critical->value,
        ], true);

        $regulatorRequired = $existingRegulatorRequired;
        if ($regulatorRequired === null) {
            $regulatorRequired = $personalData && ($highRisk || $specialCategory);
        }

        $customerRequired = $existingCustomerRequired;
        if ($customerRequired === null) {
            $customerRequired = $personalData && $highRisk;
        }

        // Escalate requirements when risk becomes high/critical.
        if ($personalData && ($highRisk || $specialCategory)) {
            $regulatorRequired = true;
        }
        if ($personalData && $highRisk) {
            $customerRequired = true;
        }

        $deadline = $existingDeadline;
        if ($regulatorRequired && blank($deadline)) {
            $deadline = now()->parse($discoveredAt)->addHours(72);
        }

        return [
            'regulator_notification_required' => (bool) $regulatorRequired,
            'customer_notification_required' => (bool) $customerRequired,
            'regulator_deadline_at' => $deadline,
        ];
    }

    private function resolveCompanyId(?string $companyIdentifier): ?int
    {
        if (blank($companyIdentifier)) {
            return null;
        }

        $company = $this->companyRepository->findByIdentifierOrFail($companyIdentifier);

        return $company->id;
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
