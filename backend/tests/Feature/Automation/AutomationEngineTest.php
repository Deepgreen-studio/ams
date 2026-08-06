<?php

namespace Tests\Feature\Automation;

use App\Domains\Automation\Enums\AutomationActionType;
use App\Domains\Automation\Enums\AutomationConditionOperator;
use App\Domains\Automation\Enums\AutomationEventKey;
use App\Domains\Automation\Enums\AutomationLogStatus;
use App\Domains\Automation\Enums\AutomationTriggerType;
use App\Domains\Automation\Models\AutomationAction;
use App\Domains\Automation\Models\AutomationCondition;
use App\Domains\Automation\Models\AutomationLog;
use App\Domains\Automation\Models\AutomationRule;
use App\Domains\Automation\Services\AutomationEngineService;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutomationEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'automation-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Automation Tenant Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_admin_can_create_and_list_automation_rules(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/automation/rules', [
            'name' => 'Ticket email rule',
            'description' => 'Send email when ticket created',
            'trigger_type' => AutomationTriggerType::Event->value,
            'event_key' => AutomationEventKey::TicketCreated->value,
            'condition_logic' => 'and',
            'is_enabled' => true,
            'conditions' => [
                [
                    'field' => 'priority',
                    'operator' => AutomationConditionOperator::Equals->value,
                    'value' => 'high',
                ],
            ],
            'actions' => [
                [
                    'action_type' => AutomationActionType::SendNotification->value,
                    'config' => [
                        'title' => 'Ticket created',
                        'message' => 'Priority {{priority}} ticket opened',
                    ],
                ],
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rule.name', 'Ticket email rule');

        $this->getJson('/api/v1/automation/rules')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('automation_rules', [
            'name' => 'Ticket email rule',
            'event_key' => AutomationEventKey::TicketCreated->value,
        ]);
        $this->assertDatabaseCount('automation_conditions', 1);
        $this->assertDatabaseCount('automation_actions', 1);
    }

    public function test_rule_can_be_toggled_and_disabled_rules_do_not_run(): void
    {
        Sanctum::actingAs($this->admin);

        $rule = $this->makeRule(
            AutomationEventKey::TicketCreated->value,
            [['field' => 'priority', 'operator' => 'equals', 'value' => 'high']],
            [[
                'action_type' => AutomationActionType::SendNotification->value,
                'config' => ['title' => 'Hi', 'message' => 'There'],
            ]]
        );

        $this->postJson("/api/v1/automation/rules/{$rule->uuid}/toggle", [
            'is_enabled' => false,
        ])->assertOk()->assertJsonPath('data.rule.is_enabled', false);

        $engine = app(AutomationEngineService::class);
        $results = $engine->handleEvent(AutomationEventKey::TicketCreated->value, [
            'priority' => 'high',
            'company_id' => $this->company->id,
            'actor_id' => $this->admin->id,
        ], $this->admin);

        $this->assertSame([], $results);
        $this->assertDatabaseCount('automation_logs', 0);
    }

    public function test_event_rule_runs_when_conditions_pass(): void
    {
        $rule = $this->makeRule(
            AutomationEventKey::TicketCreated->value,
            [['field' => 'priority', 'operator' => 'equals', 'value' => 'high']],
            [[
                'action_type' => AutomationActionType::SendNotification->value,
                'config' => [
                    'title' => 'Ticket alert',
                    'message' => 'New {{priority}} ticket',
                    'user_uuids' => [$this->admin->uuid],
                ],
            ]]
        );

        $engine = app(AutomationEngineService::class);
        $results = $engine->handleEvent(AutomationEventKey::TicketCreated->value, [
            'priority' => 'high',
            'subject' => 'Broken login',
            'company_id' => $this->company->id,
        ], $this->admin);

        $this->assertCount(1, $results);
        $this->assertSame('success', $results[0]['status']);
        $this->assertDatabaseHas('automation_logs', [
            'automation_rule_id' => $rule->id,
            'status' => AutomationLogStatus::Success->value,
            'event_key' => AutomationEventKey::TicketCreated->value,
        ]);
    }

    public function test_event_rule_skips_when_conditions_fail(): void
    {
        $rule = $this->makeRule(
            AutomationEventKey::CustomerCreated->value,
            [['field' => 'status', 'operator' => 'equals', 'value' => 'active']],
            [[
                'action_type' => AutomationActionType::SendNotification->value,
                'config' => [
                    'title' => 'Welcome',
                    'message' => 'Hello',
                    'user_uuids' => [$this->admin->uuid],
                ],
            ]]
        );

        $engine = app(AutomationEngineService::class);
        $results = $engine->handleEvent(AutomationEventKey::CustomerCreated->value, [
            'status' => 'inactive',
            'company_id' => $this->company->id,
        ], $this->admin);

        $this->assertSame('skipped', $results[0]['status']);
        $this->assertDatabaseHas('automation_logs', [
            'automation_rule_id' => $rule->id,
            'status' => AutomationLogStatus::Skipped->value,
        ]);
    }

    public function test_scheduled_rule_is_processed_by_command(): void
    {
        $rule = AutomationRule::query()->create([
            'name' => 'Scheduled heartbeat',
            'trigger_type' => AutomationTriggerType::Schedule->value,
            'event_key' => 'schedule.run',
            'schedule_cron' => '*/5 * * * *',
            'schedule_timezone' => 'UTC',
            'is_enabled' => true,
            'priority' => 50,
            'next_run_at' => now()->subMinute(),
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        AutomationAction::query()->create([
            'automation_rule_id' => $rule->id,
            'action_type' => AutomationActionType::SendNotification->value,
            'config' => [
                'title' => 'Heartbeat',
                'message' => 'OK',
                'user_uuids' => [$this->admin->uuid],
            ],
            'is_enabled' => true,
            'sort_order' => 0,
        ]);

        $this->artisan('automation:process')->assertSuccessful();

        $this->assertDatabaseHas('automation_logs', [
            'automation_rule_id' => $rule->id,
            'status' => AutomationLogStatus::Success->value,
        ]);

        $rule->refresh();
        $this->assertNotNull($rule->next_run_at);
        $this->assertTrue($rule->next_run_at->isFuture());
    }

    public function test_dashboard_and_logs_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/automation/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['statistics', 'log_statistics', 'catalog']]);

        $this->getJson('/api/v1/automation/logs')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /**
     * @param  list<array<string, mixed>>  $conditions
     * @param  list<array<string, mixed>>  $actions
     */
    private function makeRule(string $eventKey, array $conditions, array $actions): AutomationRule
    {
        $rule = AutomationRule::query()->create([
            'name' => 'Test rule '.$eventKey,
            'trigger_type' => AutomationTriggerType::Event->value,
            'event_key' => $eventKey,
            'condition_logic' => 'and',
            'is_enabled' => true,
            'priority' => 10,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

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
                'is_enabled' => true,
                'sort_order' => $index,
            ]);
        }

        return $rule->fresh(['conditions', 'actions']);
    }
}
