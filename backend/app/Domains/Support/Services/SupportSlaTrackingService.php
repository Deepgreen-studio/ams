<?php

namespace App\Domains\Support\Services;

use App\Domains\Support\Enums\SupportSlaEscalationLevel;
use App\Domains\Support\Enums\SupportSlaEscalationStatus;
use App\Domains\Support\Enums\SupportSlaEscalationTrigger;
use App\Domains\Support\Enums\SupportSlaMetric;
use App\Domains\Support\Enums\SupportSlaStatus;
use App\Domains\Support\Enums\SupportTicketMessageVisibility;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Events\SupportTicketSlaBreached;
use App\Domains\Support\Events\SupportTicketSlaEscalated;
use App\Domains\Support\Events\SupportTicketSlaWarning;
use App\Domains\Support\Models\SupportSlaEscalationRule;
use App\Domains\Support\Models\SupportSlaPolicy;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Repositories\SupportSlaEscalationRepository;
use App\Domains\Support\Repositories\SupportSlaHolidayRepository;
use App\Domains\Support\Repositories\SupportSlaPolicyRepository;
use App\Domains\Support\Repositories\SupportTicketRepository;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupportSlaTrackingService
{
    public function __construct(
        private readonly SupportSlaPolicyRepository $policyRepository,
        private readonly SupportSlaHolidayRepository $holidayRepository,
        private readonly SupportSlaEscalationRepository $escalationRepository,
        private readonly SupportTicketRepository $ticketRepository,
        private readonly SupportBusinessHoursService $businessHoursService,
    ) {}

    public function initializeForTicket(SupportTicket $ticket): SupportTicket
    {
        $priority = $ticket->priority?->value ?? (string) $ticket->priority;
        $category = $ticket->category?->value ?? (string) $ticket->category;
        $policy = $this->policyRepository->resolveForTicket((int) $ticket->company_id, $priority, $category);

        if (! $policy) {
            return $this->ticketRepository->updateTicket($ticket, [
                'support_sla_policy_id' => null,
                'sla_status' => SupportSlaStatus::NotApplicable->value,
                'first_response_due_at' => null,
                'resolution_due_at' => null,
            ]);
        }

        $start = CarbonImmutable::parse($ticket->created_at ?? now());
        $dueDates = $this->calculateDueDates($policy, $start, (int) $ticket->company_id);

        return $this->ticketRepository->updateTicket($ticket, [
            'support_sla_policy_id' => $policy->id,
            'sla_status' => SupportSlaStatus::OnTrack->value,
            'first_response_due_at' => $dueDates['response'],
            'resolution_due_at' => $dueDates['resolution'],
            'first_response_at' => null,
            'resolved_at' => null,
            'response_breached_at' => null,
            'resolution_breached_at' => null,
            'sla_paused_at' => null,
            'sla_paused_seconds' => 0,
            'escalation_level' => null,
        ]);
    }

    public function recordFirstResponse(SupportTicket $ticket, User $actor, SupportTicketMessageVisibility $visibility): SupportTicket
    {
        if ($visibility === SupportTicketMessageVisibility::Internal) {
            return $ticket;
        }

        if ($ticket->first_response_at !== null) {
            return $ticket;
        }

        $ticket = $this->ticketRepository->updateTicket($ticket, [
            'first_response_at' => now(),
            'updated_by' => $actor->id,
        ]);

        return $this->evaluateTicket($ticket);
    }

    public function handleStatusChange(SupportTicket $ticket, SupportTicketStatus $from, SupportTicketStatus $to, ?User $actor = null): SupportTicket
    {
        if ($to === SupportTicketStatus::WaitingForCustomer && $ticket->sla_paused_at === null) {
            $ticket = $this->ticketRepository->updateTicket($ticket, [
                'sla_paused_at' => now(),
                'sla_status' => SupportSlaStatus::Paused->value,
                'updated_by' => $actor?->id ?? $ticket->updated_by,
            ]);
        }

        if ($from === SupportTicketStatus::WaitingForCustomer && $to !== SupportTicketStatus::WaitingForCustomer) {
            $pausedAt = $ticket->sla_paused_at;
            $pausedSeconds = (int) $ticket->sla_paused_seconds;
            $justPaused = 0;

            if ($pausedAt) {
                $justPaused = max(0, $pausedAt->diffInSeconds(now()));
                $pausedSeconds += $justPaused;
            }

            $ticket = $this->ticketRepository->updateTicket($ticket, [
                'sla_paused_at' => null,
                'sla_paused_seconds' => $pausedSeconds,
                'updated_by' => $actor?->id ?? $ticket->updated_by,
            ]);

            $ticket = $this->extendDueDatesForPause($ticket, $justPaused);
        }

        if (in_array($to, [SupportTicketStatus::Resolved, SupportTicketStatus::Closed], true)
            && $ticket->resolved_at === null
        ) {
            $ticket = $this->ticketRepository->updateTicket($ticket, [
                'resolved_at' => now(),
                'updated_by' => $actor?->id ?? $ticket->updated_by,
            ]);
        }

        if ($to === SupportTicketStatus::Reopened) {
            $ticket = $this->ticketRepository->updateTicket($ticket, [
                'resolved_at' => null,
                'resolution_breached_at' => null,
                'updated_by' => $actor?->id ?? $ticket->updated_by,
            ]);

            if ($ticket->support_sla_policy_id) {
                $ticket = $this->initializeForTicket($ticket->fresh() ?? $ticket);
            }
        }

        if ($to === SupportTicketStatus::Cancelled) {
            return $this->ticketRepository->updateTicket($ticket, [
                'sla_status' => SupportSlaStatus::NotApplicable->value,
                'updated_by' => $actor?->id ?? $ticket->updated_by,
            ]);
        }

        return $this->evaluateTicket($ticket->fresh() ?? $ticket);
    }

    public function evaluateTicket(SupportTicket $ticket): SupportTicket
    {
        if (! $ticket->support_sla_policy_id) {
            return $ticket;
        }

        $status = $ticket->status instanceof SupportTicketStatus
            ? $ticket->status
            : SupportTicketStatus::tryFrom((string) $ticket->status);

        if ($status === SupportTicketStatus::Cancelled) {
            return $this->ticketRepository->updateTicket($ticket, [
                'sla_status' => SupportSlaStatus::NotApplicable->value,
            ]);
        }

        if ($ticket->sla_paused_at !== null || $status === SupportTicketStatus::WaitingForCustomer) {
            return $this->ticketRepository->updateTicket($ticket, [
                'sla_status' => SupportSlaStatus::Paused->value,
            ]);
        }

        $now = CarbonImmutable::now();
        $payload = [];
        $responseBreached = false;
        $resolutionBreached = false;
        $atRisk = false;

        $policy = $ticket->relationLoaded('slaPolicy')
            ? $ticket->slaPolicy
            : $ticket->slaPolicy()->with('calendar')->first();

        if ($ticket->first_response_at === null && $ticket->first_response_due_at) {
            if ($now->greaterThan(CarbonImmutable::parse($ticket->first_response_due_at))) {
                $responseBreached = true;
                $payload['response_breached_at'] = $ticket->response_breached_at ?? now();
            } elseif ($this->isAtRisk($ticket, $policy, SupportSlaMetric::Response, $now)) {
                $atRisk = true;
            }
        }

        if ($ticket->resolved_at === null && $ticket->resolution_due_at
            && ! in_array($status, [SupportTicketStatus::Resolved, SupportTicketStatus::Closed], true)
        ) {
            if ($now->greaterThan(CarbonImmutable::parse($ticket->resolution_due_at))) {
                $resolutionBreached = true;
                $payload['resolution_breached_at'] = $ticket->resolution_breached_at ?? now();
            } elseif ($this->isAtRisk($ticket, $policy, SupportSlaMetric::Resolution, $now)) {
                $atRisk = true;
            }
        }

        $met = $ticket->resolved_at !== null
            && $ticket->resolution_due_at
            && CarbonImmutable::parse($ticket->resolved_at)->lte(CarbonImmutable::parse($ticket->resolution_due_at))
            && ($ticket->first_response_at === null || $ticket->first_response_due_at === null
                || CarbonImmutable::parse($ticket->first_response_at)->lte(CarbonImmutable::parse($ticket->first_response_due_at)));

        if ($responseBreached || $resolutionBreached) {
            $payload['sla_status'] = SupportSlaStatus::Breached->value;
        } elseif ($met && in_array($status, [SupportTicketStatus::Resolved, SupportTicketStatus::Closed], true)) {
            $payload['sla_status'] = SupportSlaStatus::Met->value;
        } elseif ($atRisk) {
            $payload['sla_status'] = SupportSlaStatus::AtRisk->value;
        } else {
            $payload['sla_status'] = SupportSlaStatus::OnTrack->value;
        }

        $updated = $this->ticketRepository->updateTicket($ticket, $payload);

        $previousStatus = $ticket->sla_status instanceof SupportSlaStatus
            ? $ticket->sla_status
            : SupportSlaStatus::tryFrom((string) $ticket->sla_status);

        $enteredAtRisk = ($payload['sla_status'] ?? null) === SupportSlaStatus::AtRisk->value
            && $previousStatus !== SupportSlaStatus::AtRisk;

        if ($enteredAtRisk) {
            $metric = $ticket->first_response_at === null && $ticket->first_response_due_at
                ? SupportSlaMetric::Response->value
                : SupportSlaMetric::Resolution->value;
            event(new SupportTicketSlaWarning($updated, $metric));
        }

        if ($responseBreached && $ticket->response_breached_at === null) {
            event(new SupportTicketSlaBreached($updated, SupportSlaMetric::Response->value));
        }
        if ($resolutionBreached && $ticket->resolution_breached_at === null) {
            event(new SupportTicketSlaBreached($updated, SupportSlaMetric::Resolution->value));
        }

        $this->processEscalations($updated, $policy);

        return $updated->fresh(['slaPolicy.calendar', 'slaPolicy.escalationRules']) ?? $updated;
    }

    /**
     * @return int Number of tickets evaluated
     */
    public function evaluateOpenTickets(?int $companyId = null, int $limit = 200): int
    {
        $query = SupportTicket::query()
            ->with(['slaPolicy.calendar', 'slaPolicy.escalationRules'])
            ->whereNotNull('support_sla_policy_id')
            ->whereNotIn('status', [
                SupportTicketStatus::Cancelled->value,
            ])
            ->where(function ($builder): void {
                $builder->whereNull('resolved_at')
                    ->orWhere('sla_status', SupportSlaStatus::Breached->value)
                    ->orWhere('sla_status', SupportSlaStatus::AtRisk->value)
                    ->orWhere('sla_status', SupportSlaStatus::OnTrack->value)
                    ->orWhere('sla_status', SupportSlaStatus::Paused->value);
            });

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $count = 0;
        $query->orderBy('id')->limit($limit)->get()->each(function (SupportTicket $ticket) use (&$count): void {
            $this->evaluateTicket($ticket);
            $count++;
        });

        return $count;
    }

    /**
     * @return array{response: CarbonImmutable, resolution: CarbonImmutable}
     */
    protected function calculateDueDates(SupportSlaPolicy $policy, CarbonImmutable $start, int $companyId): array
    {
        $calendar = $policy->business_hours_only ? $policy->calendar : null;
        $holidays = $calendar
            ? $this->holidayRepository->forCalendarScope($calendar->id, $companyId)
            : collect();

        if ($policy->business_hours_only && $calendar) {
            return [
                'response' => $this->businessHoursService->addBusinessMinutes(
                    $start,
                    (int) $policy->response_target_minutes,
                    $calendar,
                    $holidays
                ),
                'resolution' => $this->businessHoursService->addBusinessMinutes(
                    $start,
                    (int) $policy->resolution_target_minutes,
                    $calendar,
                    $holidays
                ),
            ];
        }

        return [
            'response' => $start->addMinutes((int) $policy->response_target_minutes),
            'resolution' => $start->addMinutes((int) $policy->resolution_target_minutes),
        ];
    }

    protected function extendDueDatesForPause(SupportTicket $ticket, int $pausedSeconds): SupportTicket
    {
        if ($pausedSeconds <= 0) {
            return $ticket;
        }

        $minutes = (int) ceil($pausedSeconds / 60);
        $payload = [];

        if ($ticket->first_response_at === null && $ticket->first_response_due_at) {
            $payload['first_response_due_at'] = CarbonImmutable::parse($ticket->first_response_due_at)->addMinutes($minutes);
        }

        if ($ticket->resolved_at === null && $ticket->resolution_due_at) {
            $payload['resolution_due_at'] = CarbonImmutable::parse($ticket->resolution_due_at)->addMinutes($minutes);
        }

        return $payload === [] ? $ticket : $this->ticketRepository->updateTicket($ticket, $payload);
    }

    protected function isAtRisk(
        SupportTicket $ticket,
        ?SupportSlaPolicy $policy,
        SupportSlaMetric $metric,
        CarbonImmutable $now
    ): bool {
        if (! $policy) {
            return false;
        }

        $threshold = max(1, min(99, (int) $policy->at_risk_percent)) / 100;
        $target = $metric === SupportSlaMetric::Response
            ? (int) $policy->response_target_minutes
            : (int) $policy->resolution_target_minutes;
        $due = $metric === SupportSlaMetric::Response
            ? $ticket->first_response_due_at
            : $ticket->resolution_due_at;

        if (! $due || $target <= 0) {
            return false;
        }

        $dueAt = CarbonImmutable::parse($due);
        $elapsedTarget = (int) ceil($target * $threshold);
        $created = CarbonImmutable::parse($ticket->created_at ?? $now);
        $elapsed = $created->diffInMinutes($now);

        return $elapsed >= $elapsedTarget && $now->lt($dueAt);
    }

    protected function processEscalations(SupportTicket $ticket, ?SupportSlaPolicy $policy): void
    {
        if (! $policy) {
            return;
        }

        $rules = $policy->relationLoaded('escalationRules')
            ? $policy->escalationRules
            : $policy->escalationRules()->where('is_active', true)->get();

        $activeTriggers = $this->activeTriggers($ticket);

        /** @var Collection<int, SupportSlaEscalationRule> $rules */
        foreach ($rules as $rule) {
            if (! $rule->is_active) {
                continue;
            }

            $trigger = $rule->trigger instanceof SupportSlaEscalationTrigger
                ? $rule->trigger
                : SupportSlaEscalationTrigger::tryFrom((string) $rule->trigger);

            if (! $trigger || ! in_array($trigger, $activeTriggers, true)) {
                continue;
            }

            if ($this->escalationRepository->existsForTicketRule((int) $ticket->id, (int) $rule->id)) {
                continue;
            }

            DB::transaction(function () use ($ticket, $policy, $rule, $trigger): void {
                $escalation = $this->escalationRepository->create([
                    'support_ticket_id' => $ticket->id,
                    'support_sla_policy_id' => $policy->id,
                    'support_sla_escalation_rule_id' => $rule->id,
                    'company_id' => $ticket->company_id,
                    'level' => $rule->level?->value ?? $rule->level,
                    'trigger' => $trigger->value,
                    'metric' => $trigger->metric()->value,
                    'status' => SupportSlaEscalationStatus::Notified->value,
                    'triggered_at' => now(),
                    'assigned_to' => $rule->notify_user_id,
                    'metadata' => [
                        'notify_role' => $rule->notify_role,
                        'reassign_to_manager' => $rule->reassign_to_manager,
                    ],
                ]);

                $level = $rule->level instanceof SupportSlaEscalationLevel
                    ? $rule->level
                    : SupportSlaEscalationLevel::tryFrom((string) $rule->level);

                $currentLevel = $ticket->escalation_level instanceof SupportSlaEscalationLevel
                    ? $ticket->escalation_level
                    : SupportSlaEscalationLevel::tryFrom((string) $ticket->escalation_level);

                if ($level && (! $currentLevel || $level->rank() > $currentLevel->rank())) {
                    $this->ticketRepository->updateTicket($ticket, [
                        'escalation_level' => $level->value,
                    ]);
                }

                event(new SupportTicketSlaEscalated($escalation));
            });
        }
    }

    /**
     * @return list<SupportSlaEscalationTrigger>
     */
    protected function activeTriggers(SupportTicket $ticket): array
    {
        $triggers = [];
        $status = $ticket->sla_status instanceof SupportSlaStatus
            ? $ticket->sla_status
            : SupportSlaStatus::tryFrom((string) $ticket->sla_status);

        if ($ticket->response_breached_at || ($status === SupportSlaStatus::Breached && $ticket->first_response_at === null)) {
            $triggers[] = SupportSlaEscalationTrigger::ResponseBreached;
        }

        if ($ticket->resolution_breached_at || ($status === SupportSlaStatus::Breached && $ticket->resolved_at === null)) {
            $triggers[] = SupportSlaEscalationTrigger::ResolutionBreached;
        }

        if ($status === SupportSlaStatus::AtRisk && $ticket->first_response_at === null) {
            $triggers[] = SupportSlaEscalationTrigger::ResponseAtRisk;
        }

        if ($status === SupportSlaStatus::AtRisk && $ticket->resolved_at === null) {
            $triggers[] = SupportSlaEscalationTrigger::ResolutionAtRisk;
        }

        return $triggers;
    }
}
