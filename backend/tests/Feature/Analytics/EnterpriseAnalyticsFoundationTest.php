<?php

namespace Tests\Feature\Analytics;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsDashboardKind;
use App\Domains\Analytics\Enums\AnalyticsDashboardStatus;
use App\Domains\Analytics\Enums\AnalyticsWidgetType;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Domains\Analytics\Models\AnalyticsWidget;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnterpriseAnalyticsFoundationTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'enterprise-analytics@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_overview_returns_category_foundation_kpis(): void
    {
        Sanctum::actingAs($this->admin);

        AnalyticsEvent::factory()->count(3)->category(AnalyticsCategory::Business)->create([
            'occurred_at' => now()->subDays(2),
        ]);
        AnalyticsEvent::factory()->count(2)->category(AnalyticsCategory::Api)->create([
            'occurred_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/analytics/overview');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'kpis' => [
                        'total_events',
                        'categories_active',
                        'dashboards',
                        'saved_views',
                        'report_definitions',
                    ],
                    'categories',
                    'charts',
                    'supported_categories',
                ],
            ]);

        $this->assertGreaterThanOrEqual(5, $response->json('data.kpis.total_events'));
        $this->assertCount(8, $response->json('data.supported_categories'));
    }

    public function test_can_record_and_list_analytics_events(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/analytics/events', [
            'category' => AnalyticsCategory::Customer->value,
            'event_name' => 'customer.created',
            'event_source' => 'customers',
            'metrics' => ['count' => 1],
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.event.event_name', 'customer.created');

        $this->getJson('/api/v1/analytics/events?category=customer')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.events.meta.total', 1);

        $this->getJson('/api/v1/analytics/events/summary')
            ->assertOk()
            ->assertJsonPath('data.by_category.customer', 1);
    }

    public function test_dashboard_widget_and_data_resolution_flow(): void
    {
        Sanctum::actingAs($this->admin);

        AnalyticsEvent::factory()->count(4)->category(AnalyticsCategory::Application)->create([
            'occurred_at' => now()->subDays(2),
        ]);

        $dashboardResponse = $this->postJson('/api/v1/analytics/dashboards', [
            'name' => 'Application Health',
            'category' => AnalyticsCategory::Application->value,
            'kind' => AnalyticsDashboardKind::Dashboard->value,
            'status' => AnalyticsDashboardStatus::Published->value,
            'filters' => [
                'from' => now()->subDays(7)->toDateString(),
                'to' => now()->toDateString(),
            ],
        ]);

        $dashboardResponse->assertCreated();
        $dashboardUuid = $dashboardResponse->json('data.dashboard.uuid');

        $widgetResponse = $this->postJson("/api/v1/analytics/dashboards/{$dashboardUuid}/widgets", [
            'name' => 'App Events KPI',
            'type' => AnalyticsWidgetType::Kpi->value,
            'category' => AnalyticsCategory::Application->value,
            'query_config' => [
                'category' => AnalyticsCategory::Application->value,
                'metric' => 'event_count',
            ],
        ]);

        $widgetResponse->assertCreated()
            ->assertJsonPath('data.widget.type', 'kpi');

        $dataResponse = $this->getJson("/api/v1/analytics/dashboards/{$dashboardUuid}/data");

        $dataResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'dashboard',
                    'filters',
                    'widgets',
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, count($dataResponse->json('data.widgets')));
        $this->assertGreaterThanOrEqual(4, $dataResponse->json('data.widgets.0.data.value'));
    }

    public function test_saved_view_can_be_created_and_listed(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/analytics/dashboards', [
            'name' => 'API Last 30 Days',
            'kind' => AnalyticsDashboardKind::SavedView->value,
            'category' => AnalyticsCategory::Api->value,
            'status' => AnalyticsDashboardStatus::Published->value,
            'filters' => [
                'category' => AnalyticsCategory::Api->value,
                'from' => now()->subDays(29)->toDateString(),
                'to' => now()->toDateString(),
            ],
        ])->assertCreated();

        $this->getJson('/api/v1/analytics/dashboards?kind=saved_view')
            ->assertOk()
            ->assertJsonPath('data.dashboards.meta.total', 1);
    }

    public function test_report_definition_crud(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/analytics/reports', [
            'name' => 'Business Summary Draft',
            'category' => AnalyticsCategory::Business->value,
            'report_type' => 'tabular',
            'query_config' => ['metric' => 'event_count'],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.report.status', 'draft');

        $uuid = $create->json('data.report.uuid');

        $this->getJson('/api/v1/analytics/reports')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['reports', 'column_catalog', 'formats']]);

        $this->putJson("/api/v1/analytics/reports/{$uuid}", [
            'name' => 'Business Summary Updated',
        ])->assertOk()
            ->assertJsonPath('data.report.name', 'Business Summary Updated');

        $this->deleteJson("/api/v1/analytics/reports/{$uuid}")
            ->assertOk();

        $this->assertSoftDeleted('analytics_reports', ['uuid' => $uuid]);
    }

    public function test_system_dashboard_cannot_be_deleted(): void
    {
        Sanctum::actingAs($this->admin);

        $dashboard = AnalyticsDashboard::factory()->published()->create([
            'name' => 'System Overview',
            'slug' => 'system-overview',
            'is_system' => true,
        ]);

        AnalyticsWidget::factory()->forDashboard($dashboard)->create();

        $this->deleteJson('/api/v1/analytics/dashboards/'.$dashboard->uuid)
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_access_foundation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/analytics/overview')->assertForbidden();
        $this->postJson('/api/v1/analytics/events', [
            'category' => 'business',
            'event_name' => 'x',
        ])->assertForbidden();
    }
}
