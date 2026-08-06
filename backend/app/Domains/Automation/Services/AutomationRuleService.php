<?php

namespace App\Domains\Automation\Services;

use App\Domains\Automation\Enums\AutomationActionType;
use App\Domains\Automation\Enums\AutomationConditionOperator;
use App\Domains\Automation\Enums\AutomationEventKey;
use App\Domains\Automation\Enums\AutomationTriggerType;
use App\Domains\Automation\Events\AutomationRuleCreated;
use App\Domains\Automation\Events\AutomationRuleDeleted;
use App\Domains\Automation\Events\AutomationRuleUpdated;
use App\Domains\Automation\Models\AutomationAction;
use App\Domains\Automation\Models\AutomationCondition;
use App\Domains\Automation\Models\AutomationRule;
use App\Domains\Automation\Repositories\AutomationLogRepository;
use App\Domains\Automation\Repositories\AutomationRuleRepository;
use App\Domains\Companies\Repositories\CompanyRepository;
use App\Models\User;
use App\Shared\Exceptions\ApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AutomationRuleService
{
    public function __construct(
        private readonly AutomationRuleRepository $ruleRepository,
        private readonly AutomationLogRepository $logRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly AutomationEngineService $engineService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['company_id']) && ! is_numeric($filters['company_id'])) {
            $filters['company_id'] = $this->companyRepository
                ->findByIdentifierOrFail((string) $filters['company_id'])->id;
        }

        return $this->ruleRepository->paginateFiltered($filters);
    }

    public function find(string $identifier): AutomationRule
    {
        return $this->ruleRepository->findByIdentifierOrFail($identifier)
            ->load(['company:id,uuid,company_name', 'conditions', 'actions', 'creator:id,uuid,full_name,email', 'updater:id,uuid,full_name,email']);
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        return [
            'statistics' => $this->ruleRepository->statistics(),
            'log_statistics' => $this->logRepository->statistics(),
            'catalog' => $this->catalog(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        return [
            'trigger_types' => collect(AutomationTriggerType::cases())->map(fn ($item) => [
                'value' => $item->value,
                'label' => $item->label(),
            ])->values()->all(),
            'events' => collect(AutomationEventKey::cases())->map(fn ($item) => [
                'value' => $item->value,
                'label' => $item->label(),
                'description' => $item->description(),
                'sample_fields' => $item->sampleFields(),
            ])->values()->all(),
            'operators' => collect(AutomationConditionOperator::cases())->map(fn ($item) => [
                'value' => $item->value,
                'label' => $item->label(),
            ])->values()->all(),
            'actions' => collect(AutomationActionType::cases())->map(fn ($item) => [
                'value' => $item->value,
                'label' => $item->label(),
                'implemented' => $item->isImplemented(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): AutomationRule
    {
        return DB::transaction(function () use ($data, $actor): AutomationRule {
            $trigger = AutomationTriggerType::from((string) $data['trigger_type']);
            $payload = $this->prepareRulePayload($data, $trigger);
            $payload['created_by'] = $actor->id;
            $payload['updated_by'] = $actor->id;

            if ($trigger === AutomationTriggerType::Schedule) {
                $payload['next_run_at'] = $this->engineService->computeNextRun(
                    $payload['schedule_cron'] ?? null,
                    $payload['schedule_timezone'] ?? 'UTC'
                );
            }

            /** @var AutomationRule $rule */
            $rule = $this->ruleRepository->create($payload);
            $this->syncConditions($rule, $data['conditions'] ?? []);
            $this->syncActions($rule, $data['actions'] ?? []);

            event(new AutomationRuleCreated($rule->fresh(['conditions', 'actions']), $actor));

            return $this->find($rule->uuid);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $identifier, array $data, User $actor): AutomationRule
    {
        return DB::transaction(function () use ($identifier, $data, $actor): AutomationRule {
            $rule = $this->ruleRepository->findByIdentifierOrFail($identifier);
            $trigger = AutomationTriggerType::from((string) ($data['trigger_type'] ?? $rule->trigger_type->value));
            $payload = $this->prepareRulePayload($data, $trigger, isUpdate: true);
            $payload['updated_by'] = $actor->id;

            if ($trigger === AutomationTriggerType::Schedule) {
                $cron = $payload['schedule_cron'] ?? $rule->schedule_cron;
                $timezone = $payload['schedule_timezone'] ?? $rule->schedule_timezone;
                $payload['next_run_at'] = $this->engineService->computeNextRun($cron, $timezone);
            }

            /** @var AutomationRule $updated */
            $updated = $this->ruleRepository->update($rule->id, $payload);

            if (array_key_exists('conditions', $data)) {
                $this->syncConditions($updated, $data['conditions'] ?? []);
            }
            if (array_key_exists('actions', $data)) {
                $this->syncActions($updated, $data['actions'] ?? []);
            }

            event(new AutomationRuleUpdated($updated->fresh(['conditions', 'actions']), $actor));

            return $this->find($updated->uuid);
        });
    }

    public function toggle(string $identifier, User $actor, ?bool $enabled = null): AutomationRule
    {
        $rule = $this->ruleRepository->findByIdentifierOrFail($identifier);
        $next = $enabled ?? ! $rule->is_enabled;

        /** @var AutomationRule $updated */
        $updated = $this->ruleRepository->update($rule->id, [
            'is_enabled' => $next,
            'updated_by' => $actor->id,
        ]);

        event(new AutomationRuleUpdated($updated, $actor));

        return $this->find($updated->uuid);
    }

    public function delete(string $identifier, User $actor): void
    {
        $rule = $this->ruleRepository->findByIdentifierOrFail($identifier);
        $this->ruleRepository->delete($rule->id);
        event(new AutomationRuleDeleted($rule, $actor));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateLogs(array $filters = []): LengthAwarePaginator
    {
        if (! empty($filters['rule'])) {
            $rule = $this->ruleRepository->findByIdentifierOrFail((string) $filters['rule']);
            $filters['automation_rule_id'] = $rule->id;
        }

        return $this->logRepository->paginateFiltered($filters);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function testRun(string $identifier, array $context, User $actor): array
    {
        $rule = $this->find($identifier);

        return $this->engineService->runRule($rule, $context, $actor, $rule->event_key);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareRulePayload(array $data, AutomationTriggerType $trigger, bool $isUpdate = false): array
    {
        $payload = [];

        foreach (['name', 'description', 'condition_logic', 'priority', 'is_enabled', 'delay_minutes', 'schedule_cron', 'schedule_timezone', 'metadata'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        $payload['trigger_type'] = $trigger->value;

        if (array_key_exists('company_id', $data)) {
            $payload['company_id'] = blank($data['company_id'])
                ? null
                : $this->companyRepository->findByIdentifierOrFail((string) $data['company_id'])->id;
        }

        if ($trigger === AutomationTriggerType::Event || $trigger === AutomationTriggerType::Time) {
            $eventKey = (string) ($data['event_key'] ?? '');
            if ($eventKey === '' && ! $isUpdate) {
                throw new ApiException('Event key is required for event/time triggers.', 422);
            }
            if ($eventKey !== '') {
                if (! AutomationEventKey::tryFrom($eventKey)) {
                    throw new ApiException('Unsupported automation event key.', 422);
                }
                $payload['event_key'] = $eventKey;
            }
        }

        if ($trigger === AutomationTriggerType::Schedule) {
            $cron = (string) ($data['schedule_cron'] ?? '');
            if ($cron === '' && ! $isUpdate) {
                throw new ApiException('Cron expression is required for scheduled rules.', 422);
            }
            if ($cron !== '') {
                $payload['schedule_cron'] = $cron;
            }
            $payload['event_key'] = $data['event_key'] ?? 'schedule.run';
        }

        if (! array_key_exists('condition_logic', $payload) && ! $isUpdate) {
            $payload['condition_logic'] = 'and';
        }

        if (! array_key_exists('is_enabled', $payload) && ! $isUpdate) {
            $payload['is_enabled'] = true;
        }

        if (! array_key_exists('priority', $payload) && ! $isUpdate) {
            $payload['priority'] = 100;
        }

        return $payload;
    }

    /**
     * @param  array<int, array<string, mixed>>  $conditions
     */
    private function syncConditions(AutomationRule $rule, array $conditions): void
    {
        $rule->conditions()->delete();

        foreach (array_values($conditions) as $index => $item) {
            if (blank($item['field'] ?? null) || blank($item['operator'] ?? null)) {
                continue;
            }

            AutomationCondition::query()->create([
                'automation_rule_id' => $rule->id,
                'field' => (string) $item['field'],
                'operator' => AutomationConditionOperator::from((string) $item['operator'])->value,
                'value' => $item['value'] ?? null,
                'sort_order' => (int) ($item['sort_order'] ?? $index),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $actions
     */
    private function syncActions(AutomationRule $rule, array $actions): void
    {
        $rule->actions()->delete();

        if ($actions === []) {
            throw new ApiException('At least one action is required.', 422);
        }

        foreach (array_values($actions) as $index => $item) {
            $type = AutomationActionType::from((string) ($item['action_type'] ?? ''));

            AutomationAction::query()->create([
                'automation_rule_id' => $rule->id,
                'action_type' => $type->value,
                'config' => $item['config'] ?? [],
                'is_enabled' => array_key_exists('is_enabled', $item) ? (bool) $item['is_enabled'] : true,
                'sort_order' => (int) ($item['sort_order'] ?? $index),
            ]);
        }
    }
}
