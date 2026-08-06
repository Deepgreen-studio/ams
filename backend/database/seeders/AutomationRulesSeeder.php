<?php

namespace Database\Seeders;

use App\Domains\Automation\Enums\AutomationActionType;
use App\Domains\Automation\Enums\AutomationConditionOperator;
use App\Domains\Automation\Enums\AutomationEventKey;
use App\Domains\Automation\Enums\AutomationTriggerType;
use App\Domains\Automation\Models\AutomationAction;
use App\Domains\Automation\Models\AutomationCondition;
use App\Domains\Automation\Models\AutomationRule;
use App\Models\User;
use Illuminate\Database\Seeder;

class AutomationRulesSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->orderBy('id')->first();

        $this->seedRule(
            'Ticket Created — Notify & Assign Follow-up',
            'When a support ticket is created, send email, create a follow-up task, and assign an agent when configured.',
            AutomationTriggerType::Event,
            AutomationEventKey::TicketCreated->value,
            [
                [
                    'field' => 'priority',
                    'operator' => AutomationConditionOperator::In->value,
                    'value' => 'high,urgent,critical',
                ],
            ],
            [
                [
                    'action_type' => AutomationActionType::SendEmail->value,
                    'config' => [
                        'title' => 'New high-priority ticket',
                        'message' => 'A high-priority support ticket was created: {{subject}}',
                    ],
                ],
                [
                    'action_type' => AutomationActionType::CreateTask->value,
                    'config' => [
                        'title' => 'Follow up on ticket {{ticket_number}}',
                        'description' => 'Automated follow-up for newly created ticket.',
                    ],
                ],
                [
                    'action_type' => AutomationActionType::AssignAgent->value,
                    'config' => [
                        'assignee_id' => null,
                    ],
                ],
            ],
            $actor?->id,
        );

        $this->seedRule(
            'Customer Created — Welcome & Provision',
            'When a customer is created, send a welcome email, assign a default role, and generate an API key.',
            AutomationTriggerType::Event,
            AutomationEventKey::CustomerCreated->value,
            [],
            [
                [
                    'action_type' => AutomationActionType::SendEmail->value,
                    'config' => [
                        'title' => 'Welcome to AMS',
                        'message' => 'Welcome! Your customer account has been created.',
                    ],
                ],
                [
                    'action_type' => AutomationActionType::AssignRole->value,
                    'config' => [
                        'role' => 'customer',
                    ],
                ],
                [
                    'action_type' => AutomationActionType::GenerateApiKey->value,
                    'config' => [
                        'token_name' => 'customer-api',
                    ],
                ],
            ],
            $actor?->id,
        );

        $this->seedRule(
            'Application Released — Notify Customers',
            'When an application release is deployed, notify linked customers and queue a push placeholder.',
            AutomationTriggerType::Event,
            AutomationEventKey::ApplicationReleaseDeployed->value,
            [],
            [
                [
                    'action_type' => AutomationActionType::NotifyCustomers->value,
                    'config' => [
                        'title' => 'New release available',
                        'message' => 'Version {{version}} has been released.',
                    ],
                ],
                [
                    'action_type' => AutomationActionType::SendPush->value,
                    'config' => [
                        'title' => 'Release update',
                        'body' => 'A new application version is available.',
                    ],
                    'is_enabled' => false,
                ],
            ],
            $actor?->id,
        );

        $this->seedRule(
            'Daily Automation Health Check',
            'Scheduled rule that records a daily automation heartbeat via in-app notification to operators.',
            AutomationTriggerType::Schedule,
            'schedule.run',
            [],
            [
                [
                    'action_type' => AutomationActionType::SendNotification->value,
                    'config' => [
                        'title' => 'Automation engine heartbeat',
                        'message' => 'Scheduled automation processor is healthy.',
                        'recipient' => 'actor',
                    ],
                ],
            ],
            $actor?->id,
            [
                'schedule_cron' => '0 8 * * *',
                'schedule_timezone' => 'UTC',
                'next_run_at' => now()->addDay()->startOfDay()->setHour(8),
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $conditions
     * @param  list<array<string, mixed>>  $actions
     * @param  array<string, mixed>  $extra
     */
    private function seedRule(
        string $name,
        string $description,
        AutomationTriggerType $trigger,
        string $eventKey,
        array $conditions,
        array $actions,
        ?int $actorId,
        array $extra = [],
    ): void {
        $rule = AutomationRule::query()->firstOrCreate(
            ['name' => $name],
            array_merge([
                'description' => $description,
                'trigger_type' => $trigger->value,
                'event_key' => $eventKey,
                'condition_logic' => 'and',
                'is_enabled' => true,
                'priority' => 100,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ], $extra),
        );

        if ($rule->conditions()->exists() || $rule->actions()->exists()) {
            return;
        }

        foreach (array_values($conditions) as $index => $condition) {
            AutomationCondition::query()->create([
                'automation_rule_id' => $rule->id,
                'field' => $condition['field'],
                'operator' => $condition['operator'],
                'value' => $condition['value'] ?? null,
                'sort_order' => $index,
            ]);
        }

        foreach (array_values($actions) as $index => $action) {
            AutomationAction::query()->create([
                'automation_rule_id' => $rule->id,
                'action_type' => $action['action_type'],
                'config' => $action['config'] ?? [],
                'is_enabled' => array_key_exists('is_enabled', $action) ? (bool) $action['is_enabled'] : true,
                'sort_order' => $index,
            ]);
        }
    }
}
