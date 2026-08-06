<?php

namespace App\Domains\Automation\Services;

use App\Domains\Automation\Enums\AutomationLogStatus;
use App\Domains\Automation\Enums\AutomationTriggerType;
use App\Domains\Automation\Models\AutomationRule;
use App\Domains\Automation\Repositories\AutomationLogRepository;
use App\Domains\Automation\Repositories\AutomationRuleRepository;
use App\Models\User;
use Carbon\Carbon;
use Cron\CronExpression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomationEngineService
{
    public function __construct(
        private readonly AutomationRuleRepository $ruleRepository,
        private readonly AutomationLogRepository $logRepository,
        private readonly AutomationConditionEvaluator $conditionEvaluator,
        private readonly AutomationActionExecutor $actionExecutor,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return list<array<string, mixed>>
     */
    public function handleEvent(string $eventKey, array $context = [], ?User $actor = null): array
    {
        $results = [];
        $rules = $this->ruleRepository->enabledForEvent($eventKey);

        foreach ($rules as $rule) {
            if (! empty($context['company_id']) && $rule->company_id && (int) $rule->company_id !== (int) $context['company_id']) {
                continue;
            }

            // Time-delayed event rules: queue for later instead of running immediately.
            if ($rule->trigger_type === AutomationTriggerType::Time && ($rule->delay_minutes ?? 0) > 0) {
                $this->queueDelayedRule($rule, $eventKey, $context);
                $results[] = [
                    'rule_uuid' => $rule->uuid,
                    'status' => 'queued',
                    'message' => 'Delayed rule queued for '.$rule->delay_minutes.' minute(s).',
                ];

                continue;
            }

            $results[] = $this->runRule($rule, $context, $actor, $eventKey);
        }

        return $results;
    }

    /**
     * Process due schedule and delayed rules.
     *
     * @return array{processed: int, results: list<array<string, mixed>>}
     */
    public function processDueRules(int $limit = 50): array
    {
        $results = [];
        $processed = 0;

        foreach ($this->ruleRepository->dueScheduled($limit) as $rule) {
            $result = $this->runRule($rule, [
                'event_key' => $rule->event_key,
                'trigger' => 'schedule',
            ], null, $rule->event_key);
            $this->advanceSchedule($rule);
            $results[] = $result;
            $processed++;
        }

        foreach ($this->ruleRepository->dueDelayed($limit) as $rule) {
            $context = $rule->metadata['queued_context'] ?? [];
            $result = $this->runRule($rule, is_array($context) ? $context : [], null, $rule->event_key);
            $this->ruleRepository->update($rule->id, [
                'next_run_at' => null,
                'metadata' => array_merge($rule->metadata ?? [], ['queued_context' => null]),
            ]);
            $results[] = $result;
            $processed++;
        }

        return [
            'processed' => $processed,
            'results' => $results,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function runRule(AutomationRule $rule, array $context = [], ?User $actor = null, ?string $eventKey = null): array
    {
        $eventKey = $eventKey ?? $rule->event_key;
        $context = array_change_key_case(array_merge([
            'event_key' => $eventKey,
            'rule_uuid' => $rule->uuid,
            'rule_name' => $rule->name,
        ], $context), CASE_LOWER);

        $log = $this->logRepository->create([
            'automation_rule_id' => $rule->id,
            'status' => AutomationLogStatus::Running->value,
            'trigger_type' => $rule->trigger_type?->value ?? $rule->trigger_type,
            'event_key' => $eventKey,
            'context' => $context,
            'message' => 'Automation rule started.',
            'started_at' => now(),
        ]);

        try {
            $passes = $this->conditionEvaluator->passes(
                $rule->conditions,
                $context,
                (string) ($rule->condition_logic ?? 'and')
            );

            if (! $passes) {
                $this->logRepository->update($log->id, [
                    'status' => AutomationLogStatus::Skipped->value,
                    'message' => 'Conditions not met.',
                    'finished_at' => now(),
                ]);

                return [
                    'rule_uuid' => $rule->uuid,
                    'status' => 'skipped',
                    'message' => 'Conditions not met.',
                    'log_uuid' => $log->uuid,
                ];
            }

            $actionResults = [];
            foreach ($rule->actions->where('is_enabled', true) as $action) {
                $actionResults[] = array_merge(
                    ['action_type' => $action->action_type?->value ?? $action->action_type],
                    $this->actionExecutor->execute($action, $context, $actor)
                );
            }

            $failed = collect($actionResults)->contains(fn (array $item) => ($item['status'] ?? null) === 'failed');

            $this->logRepository->update($log->id, [
                'status' => $failed ? AutomationLogStatus::Failed->value : AutomationLogStatus::Success->value,
                'actions_result' => $actionResults,
                'message' => $failed ? 'One or more actions failed.' : 'Automation rule completed.',
                'finished_at' => now(),
            ]);

            $this->ruleRepository->update($rule->id, [
                'last_triggered_at' => now(),
            ]);

            return [
                'rule_uuid' => $rule->uuid,
                'status' => $failed ? 'failed' : 'success',
                'actions' => $actionResults,
                'log_uuid' => $log->uuid,
            ];
        } catch (Throwable $exception) {
            Log::error('Automation rule execution failed', [
                'rule' => $rule->uuid,
                'error' => $exception->getMessage(),
            ]);

            $this->logRepository->update($log->id, [
                'status' => AutomationLogStatus::Failed->value,
                'error_message' => $exception->getMessage(),
                'message' => 'Automation rule failed.',
                'finished_at' => now(),
            ]);

            return [
                'rule_uuid' => $rule->uuid,
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'log_uuid' => $log->uuid,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function queueDelayedRule(AutomationRule $rule, string $eventKey, array $context): void
    {
        $this->ruleRepository->update($rule->id, [
            'next_run_at' => now()->addMinutes(max(1, (int) $rule->delay_minutes)),
            'metadata' => array_merge($rule->metadata ?? [], [
                'queued_context' => array_merge($context, ['event_key' => $eventKey]),
                'queued_at' => now()->toIso8601String(),
            ]),
        ]);

        $this->logRepository->create([
            'automation_rule_id' => $rule->id,
            'status' => AutomationLogStatus::Queued->value,
            'trigger_type' => AutomationTriggerType::Time->value,
            'event_key' => $eventKey,
            'context' => $context,
            'message' => 'Delayed automation queued.',
            'started_at' => now(),
        ]);
    }

    public function advanceSchedule(AutomationRule $rule): void
    {
        if (blank($rule->schedule_cron)) {
            $this->ruleRepository->update($rule->id, ['next_run_at' => null]);

            return;
        }

        try {
            $timezone = $rule->schedule_timezone ?: 'UTC';
            $cron = new CronExpression((string) $rule->schedule_cron);
            $next = Carbon::instance($cron->getNextRunDate('now', 0, false, $timezone));

            $this->ruleRepository->update($rule->id, [
                'next_run_at' => $next->utc(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to advance automation schedule', [
                'rule' => $rule->uuid,
                'cron' => $rule->schedule_cron,
                'error' => $exception->getMessage(),
            ]);
            $this->ruleRepository->update($rule->id, ['next_run_at' => null]);
        }
    }

    public function computeNextRun(?string $cron, ?string $timezone = 'UTC'): ?Carbon
    {
        if (blank($cron)) {
            return null;
        }

        $expression = new CronExpression($cron);

        return Carbon::instance($expression->getNextRunDate('now', 0, false, $timezone ?: 'UTC'))->utc();
    }
}
