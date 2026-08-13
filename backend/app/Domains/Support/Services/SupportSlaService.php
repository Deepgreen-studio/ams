<?php

namespace App\Domains\Support\Services;

use App\Domains\Companies\Repositories\CompanyRepository;
use App\Domains\Support\Enums\SupportSlaEscalationLevel;
use App\Domains\Support\Enums\SupportSlaEscalationStatus;
use App\Domains\Support\Enums\SupportSlaEscalationTrigger;
use App\Domains\Support\Enums\SupportSlaStatus;
use App\Domains\Support\Models\SupportSlaCalendar;
use App\Domains\Support\Models\SupportSlaEscalation;
use App\Domains\Support\Models\SupportSlaEscalationRule;
use App\Domains\Support\Models\SupportSlaHoliday;
use App\Domains\Support\Models\SupportSlaPolicy;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Repositories\SupportSlaCalendarRepository;
use App\Domains\Support\Repositories\SupportSlaEscalationRepository;
use App\Domains\Support\Repositories\SupportSlaHolidayRepository;
use App\Domains\Support\Repositories\SupportSlaPolicyRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupportSlaService
{
    public function __construct(
        private readonly SupportSlaPolicyRepository $policyRepository,
        private readonly SupportSlaCalendarRepository $calendarRepository,
        private readonly SupportSlaHolidayRepository $holidayRepository,
        private readonly SupportSlaEscalationRepository $escalationRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly SupportSlaTrackingService $trackingService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        $companyId = $this->resolveCompanyId($filters['company_id'] ?? null);
        $ticketQuery = SupportTicket::query()->whereNotNull('support_sla_policy_id');

        if ($companyId !== null) {
            $ticketQuery->where('company_id', $companyId);
        }

        $byStatus = [];
        foreach (SupportSlaStatus::cases() as $status) {
            $byStatus[$status->value] = (clone $ticketQuery)->where('sla_status', $status->value)->count();
        }

        $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));

        $timers = (clone $ticketQuery)
            ->with(['company:id,uuid,company_name', 'assignee:id,uuid,full_name', 'slaPolicy:id,uuid,name'])
            ->whereIn('sla_status', [
                SupportSlaStatus::OnTrack->value,
                SupportSlaStatus::AtRisk->value,
                SupportSlaStatus::Breached->value,
                SupportSlaStatus::Paused->value,
            ])
            ->whereNull('resolved_at')
            ->orderByRaw("CASE sla_status WHEN 'breached' THEN 1 WHEN 'at_risk' THEN 2 WHEN 'paused' THEN 3 ELSE 4 END")
            ->orderBy('first_response_due_at')
            ->paginate(
                $perPage,
                ['*'],
                'page',
                max(1, (int) ($filters['page'] ?? 1)),
            );

        return [
            'statistics' => [
                'tracked_tickets' => (clone $ticketQuery)->count(),
                'on_track' => $byStatus[SupportSlaStatus::OnTrack->value] ?? 0,
                'at_risk' => $byStatus[SupportSlaStatus::AtRisk->value] ?? 0,
                'breached' => $byStatus[SupportSlaStatus::Breached->value] ?? 0,
                'paused' => $byStatus[SupportSlaStatus::Paused->value] ?? 0,
                'met' => $byStatus[SupportSlaStatus::Met->value] ?? 0,
                'response_violations' => (clone $ticketQuery)->whereNotNull('response_breached_at')->count(),
                'resolution_violations' => (clone $ticketQuery)->whereNotNull('resolution_breached_at')->count(),
                'escalations' => $this->escalationRepository->statistics($companyId),
            ],
            'timers' => $timers,
            'by_status' => $byStatus,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function escalationQueue(array $filters = []): LengthAwarePaginator
    {
        $filters['company_id'] = $this->resolveCompanyId($filters['company_id'] ?? null);

        return $this->escalationRepository->paginateQueue($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: LengthAwarePaginator, summary: array<string, int>}
     */
    public function violationReport(array $filters = []): array
    {
        $companyId = $this->resolveCompanyId($filters['company_id'] ?? null);
        $query = SupportTicket::query()
            ->with(['company:id,uuid,company_name', 'assignee:id,uuid,full_name,email', 'slaPolicy:id,uuid,name'])
            ->where(function ($builder): void {
                $builder->whereNotNull('response_breached_at')
                    ->orWhereNotNull('resolution_breached_at')
                    ->orWhere('sla_status', SupportSlaStatus::Breached->value);
            });

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if (! blank($filters['metric'] ?? null)) {
            if ($filters['metric'] === 'response') {
                $query->whereNotNull('response_breached_at');
            }
            if ($filters['metric'] === 'resolution') {
                $query->whereNotNull('resolution_breached_at');
            }
        }

        $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));

        $items = $query->orderByDesc('response_breached_at')
            ->orderByDesc('resolution_breached_at')
            ->paginate(
                $perPage,
                ['*'],
                'page',
                max(1, (int) ($filters['page'] ?? 1)),
            );

        return [
            'items' => $items,
            'summary' => [
                'total' => $items->total(),
                'response' => SupportTicket::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNotNull('response_breached_at')
                    ->count(),
                'resolution' => SupportTicket::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNotNull('resolution_breached_at')
                    ->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listPolicies(array $filters = []): LengthAwarePaginator
    {
        $filters['company_id'] = $this->resolveCompanyId($filters['company_id'] ?? null);

        return $this->policyRepository->paginate($filters);
    }

    public function findPolicy(string $identifier): SupportSlaPolicy
    {
        return $this->policyRepository->findByIdentifierOrFail($identifier);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPolicy(array $data, User $actor): SupportSlaPolicy
    {
        return DB::transaction(function () use ($data, $actor): SupportSlaPolicy {
            $payload = $this->preparePolicyPayload($data, $actor, isCreate: true);
            $policy = $this->policyRepository->create($payload);

            foreach ($data['escalation_rules'] ?? [] as $index => $rule) {
                $this->createRule($policy, $rule, $index);
            }

            return $policy->fresh(['calendar', 'company', 'escalationRules']) ?? $policy;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePolicy(string $identifier, array $data, User $actor): SupportSlaPolicy
    {
        return DB::transaction(function () use ($identifier, $data, $actor): SupportSlaPolicy {
            $policy = $this->policyRepository->findByIdentifierOrFail($identifier);
            $payload = $this->preparePolicyPayload($data, $actor, isCreate: false);
            $policy = $this->policyRepository->update($policy, $payload);

            if (array_key_exists('escalation_rules', $data)) {
                $policy->escalationRules()->delete();
                foreach ($data['escalation_rules'] ?? [] as $index => $rule) {
                    $this->createRule($policy, $rule, $index);
                }
            }

            return $policy->fresh(['calendar', 'company', 'escalationRules']) ?? $policy;
        });
    }

    public function deletePolicy(string $identifier): void
    {
        $policy = $this->policyRepository->findByIdentifierOrFail($identifier);
        $policy->delete();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listCalendars(array $filters = []): LengthAwarePaginator
    {
        $filters['company_id'] = $this->resolveCompanyId($filters['company_id'] ?? null);

        return $this->calendarRepository->paginate($filters);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCalendar(array $data, User $actor): SupportSlaCalendar
    {
        $payload = $this->prepareCalendarPayload($data, $actor, true);

        return $this->calendarRepository->create($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCalendar(string $identifier, array $data, User $actor): SupportSlaCalendar
    {
        $calendar = $this->calendarRepository->findByIdentifierOrFail($identifier);
        $payload = $this->prepareCalendarPayload($data, $actor, false);

        return $this->calendarRepository->update($calendar, $payload);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listHolidays(array $filters = []): LengthAwarePaginator
    {
        $filters['company_id'] = $this->resolveCompanyId($filters['company_id'] ?? null);

        if (! blank($filters['calendar'] ?? null)) {
            $calendar = $this->calendarRepository->findByIdentifierOrFail((string) $filters['calendar']);
            $filters['calendar_id'] = $calendar->id;
        }

        return $this->holidayRepository->paginate($filters);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createHoliday(array $data, User $actor): SupportSlaHoliday
    {
        return $this->holidayRepository->create($this->prepareHolidayPayload($data, $actor));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateHoliday(string $identifier, array $data, User $actor): SupportSlaHoliday
    {
        $holiday = $this->holidayRepository->findByIdentifierOrFail($identifier);

        return $this->holidayRepository->update($holiday, $this->prepareHolidayPayload($data, $actor, false));
    }

    public function deleteHoliday(string $identifier): void
    {
        $holiday = $this->holidayRepository->findByIdentifierOrFail($identifier);
        $this->holidayRepository->delete($holiday);
    }

    public function acknowledgeEscalation(string $identifier, User $actor, ?string $notes = null): SupportSlaEscalation
    {
        $escalation = $this->escalationRepository->findByIdentifierOrFail($identifier);

        return $this->escalationRepository->update($escalation, [
            'status' => SupportSlaEscalationStatus::Acknowledged->value,
            'acknowledged_at' => now(),
            'acknowledged_by' => $actor->id,
            'notes' => $notes ?? $escalation->notes,
        ]);
    }

    public function resolveEscalation(string $identifier, User $actor, ?string $notes = null): SupportSlaEscalation
    {
        $escalation = $this->escalationRepository->findByIdentifierOrFail($identifier);

        return $this->escalationRepository->update($escalation, [
            'status' => SupportSlaEscalationStatus::Resolved->value,
            'resolved_at' => now(),
            'acknowledged_by' => $escalation->acknowledged_by ?? $actor->id,
            'acknowledged_at' => $escalation->acknowledged_at ?? now(),
            'notes' => $notes ?? $escalation->notes,
        ]);
    }

    public function evaluate(int $limit = 200): int
    {
        return $this->trackingService->evaluateOpenTickets(null, $limit);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePolicyPayload(array $data, User $actor, bool $isCreate): array
    {
        $payload = [
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
            'priority' => $data['priority'] ?? null,
            'category' => $data['category'] ?? null,
            'response_target_minutes' => $data['response_target_minutes'] ?? null,
            'resolution_target_minutes' => $data['resolution_target_minutes'] ?? null,
            'at_risk_percent' => $data['at_risk_percent'] ?? 80,
            'business_hours_only' => array_key_exists('business_hours_only', $data)
                ? (bool) $data['business_hours_only']
                : true,
            'is_default' => (bool) ($data['is_default'] ?? false),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'description' => $data['description'] ?? null,
            'updated_by' => $actor->id,
        ];

        if ($isCreate) {
            $payload['created_by'] = $actor->id;
            $payload['company_id'] = $this->resolveCompanyId($data['company_id'] ?? null);
        } elseif (array_key_exists('company_id', $data)) {
            $payload['company_id'] = $this->resolveCompanyId($data['company_id']);
        }

        if (! blank($data['calendar_id'] ?? null)) {
            $calendar = $this->calendarRepository->findByIdentifierOrFail((string) $data['calendar_id']);
            $payload['support_sla_calendar_id'] = $calendar->id;
        } elseif (array_key_exists('calendar_id', $data) && blank($data['calendar_id'])) {
            $payload['support_sla_calendar_id'] = null;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    protected function createRule(SupportSlaPolicy $policy, array $rule, int $index): SupportSlaEscalationRule
    {
        $level = SupportSlaEscalationLevel::tryFrom((string) ($rule['level'] ?? ''));
        $trigger = SupportSlaEscalationTrigger::tryFrom((string) ($rule['trigger'] ?? ''));

        if (! $level || ! $trigger) {
            throw new ApiException('Invalid escalation rule level or trigger.', 422);
        }

        return SupportSlaEscalationRule::query()->create([
            'support_sla_policy_id' => $policy->id,
            'level' => $level->value,
            'trigger' => $trigger->value,
            'sort_order' => (int) ($rule['sort_order'] ?? $index),
            'notify_role' => $rule['notify_role'] ?? null,
            'notify_user_id' => null,
            'reassign_to_manager' => (bool) ($rule['reassign_to_manager'] ?? false),
            'is_active' => array_key_exists('is_active', $rule) ? (bool) $rule['is_active'] : true,
            'metadata' => $rule['metadata'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareCalendarPayload(array $data, User $actor, bool $isCreate): array
    {
        $payload = [
            'name' => $data['name'],
            'timezone' => $data['timezone'] ?? 'UTC',
            'business_hours' => $data['business_hours'],
            'is_default' => (bool) ($data['is_default'] ?? false),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'updated_by' => $actor->id,
        ];

        if ($isCreate) {
            $payload['created_by'] = $actor->id;
            $payload['company_id'] = $this->resolveCompanyId($data['company_id'] ?? null);
        } elseif (array_key_exists('company_id', $data)) {
            $payload['company_id'] = $this->resolveCompanyId($data['company_id']);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareHolidayPayload(array $data, User $actor, bool $isCreate = true): array
    {
        $payload = [
            'name' => $data['name'] ?? null,
            'holiday_date' => $data['holiday_date'] ?? null,
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'updated_by' => $actor->id,
        ];

        if ($isCreate || array_key_exists('company_id', $data)) {
            $payload['company_id'] = $this->resolveCompanyId($data['company_id'] ?? null);
        }

        if ($isCreate) {
            $payload['created_by'] = $actor->id;
        }

        if (! blank($data['calendar_id'] ?? null)) {
            $calendar = $this->calendarRepository->findByIdentifierOrFail((string) $data['calendar_id']);
            $payload['support_sla_calendar_id'] = $calendar->id;
        } elseif (array_key_exists('calendar_id', $data) && blank($data['calendar_id'])) {
            $payload['support_sla_calendar_id'] = null;
        }

        return array_filter($payload, fn ($value, $key) => $value !== null || in_array($key, ['company_id', 'support_sla_calendar_id'], true), ARRAY_FILTER_USE_BOTH);
    }

    protected function resolveCompanyId(mixed $identifier): ?int
    {
        if (blank($identifier)) {
            return null;
        }

        if (is_numeric($identifier)) {
            return (int) $identifier;
        }

        return $this->companyRepository->findByIdentifierOrFail((string) $identifier)->id;
    }
}
