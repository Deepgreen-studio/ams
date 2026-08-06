<?php

namespace Tests\Feature\Analytics;

use App\Domains\Automation\Enums\AutomationLogStatus;
use App\Domains\Automation\Models\AutomationLog;
use App\Domains\Automation\Models\AutomationRule;
use App\Domains\Notifications\Enums\NotificationDeliveryStatus;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationLog;
use App\Domains\Workflows\Enums\WorkflowInstanceStatus;
use App\Domains\Workflows\Models\Workflow;
use App\Domains\Workflows\Models\WorkflowInstance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'analytics-admin@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_dashboard_aggregates_notification_automation_workflow_metrics(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedSampleData();

        $response = $this->getJson('/api/v1/analytics/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'kpis' => [
                        'notifications_sent',
                        'notifications_failed',
                        'read_rate',
                        'click_rate',
                        'automation_executions',
                        'workflow_success_rate',
                        'ai_requests',
                    ],
                    'charts',
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, $response->json('data.kpis.notifications_sent'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.kpis.automation_executions'));
    }

    public function test_delivery_and_automation_reports_return_rates(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedSampleData();

        $this->getJson('/api/v1/analytics/notifications')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'sent',
                    'failed',
                    'avg_delivery_seconds',
                    'read_rate',
                    'click_rate',
                    'trends',
                ],
            ]);

        $this->getJson('/api/v1/analytics/automation')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'success_rate',
                    'avg_processing_seconds',
                    'top_rules',
                ],
            ]);

        $this->getJson('/api/v1/analytics/workflows')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'success_rate',
                    'failures',
                    'avg_processing_seconds',
                ],
            ]);
    }

    public function test_csv_export_downloads_and_pdf_is_architecture_ready(): void
    {
        Sanctum::actingAs($this->admin);

        $csv = $this->get('/api/v1/analytics/export?format=csv&report=overview');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));

        $this->getJson('/api/v1/analytics/export?format=pdf&report=overview')
            ->assertStatus(422)
            ->assertJsonPath('data.pdf_ready', true);
    }

    private function seedSampleData(): void
    {
        $notification = Notification::query()->create([
            'user_id' => $this->admin->id,
            'channel' => 'in_app',
            'event_key' => 'support.ticket_created',
            'title' => 'Analytics Test',
            'message' => 'Hello',
            'status' => NotificationStatus::Sent->value,
            'sent_at' => now()->subMinutes(5),
            'read_at' => now()->subMinutes(2),
            'clicked_at' => now()->subMinute(),
            'click_count' => 1,
            'created_by' => $this->admin->id,
        ]);

        NotificationLog::query()->create([
            'notification_id' => $notification->id,
            'notifiable_type' => User::class,
            'notifiable_id' => $this->admin->id,
            'event_key' => 'support.ticket_created',
            'channel' => 'in_app',
            'status' => NotificationDeliveryStatus::Sent->value,
            'recipient' => $this->admin->email,
            'queued_at' => now()->subMinutes(6),
            'sent_at' => now()->subMinutes(5),
        ]);

        NotificationLog::query()->create([
            'notification_id' => $notification->id,
            'notifiable_type' => User::class,
            'notifiable_id' => $this->admin->id,
            'event_key' => 'support.ticket_created',
            'channel' => 'email',
            'status' => NotificationDeliveryStatus::Failed->value,
            'recipient' => $this->admin->email,
            'error_message' => 'SMTP timeout',
            'queued_at' => now()->subMinutes(6),
            'failed_at' => now()->subMinutes(5),
        ]);

        $rule = AutomationRule::query()->create([
            'name' => 'Analytics Rule',
            'trigger_type' => 'event',
            'event_key' => 'support.ticket_created',
            'is_enabled' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        AutomationLog::query()->create([
            'automation_rule_id' => $rule->id,
            'status' => AutomationLogStatus::Success->value,
            'trigger_type' => 'event',
            'event_key' => 'support.ticket_created',
            'started_at' => now()->subMinutes(4),
            'finished_at' => now()->subMinutes(3),
        ]);

        $workflow = Workflow::query()->create([
            'name' => 'Analytics Workflow',
            'type' => 'approval',
            'status' => 'active',
            'is_enabled' => true,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        WorkflowInstance::query()->create([
            'workflow_id' => $workflow->id,
            'status' => WorkflowInstanceStatus::Completed->value,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(2),
            'started_by' => $this->admin->id,
        ]);

        WorkflowInstance::query()->create([
            'workflow_id' => $workflow->id,
            'status' => WorkflowInstanceStatus::Rejected->value,
            'started_at' => now()->subMinutes(8),
            'completed_at' => now()->subMinutes(7),
            'started_by' => $this->admin->id,
        ]);
    }
}
