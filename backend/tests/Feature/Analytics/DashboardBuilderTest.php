<?php

namespace Tests\Feature\Analytics;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsDashboardVisibility;
use App\Domains\Analytics\Enums\AnalyticsWidgetType;
use App\Domains\Analytics\Models\AnalyticsDashboard;
use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Domains\Analytics\Models\AnalyticsWidget;
use App\Domains\Roles\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardBuilderTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'dashboard-builder@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_widget_library_returns_builder_catalog(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/analytics/widgets/library')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'groups',
                    'widgets',
                    'grid' => ['columns', 'row_height', 'gap'],
                ],
            ]);

        $types = collect($this->getJson('/api/v1/analytics/widgets/library')->json('data.widgets'))
            ->pluck('type')
            ->all();

        $this->assertContains('map', $types);
        $this->assertContains('activity_feed', $types);
        $this->assertContains('notifications', $types);
    }

    public function test_can_create_personal_company_and_default_dashboards(): void
    {
        Sanctum::actingAs($this->admin);

        $personal = $this->postJson('/api/v1/analytics/dashboards', [
            'name' => 'My Personal Board',
            'visibility' => AnalyticsDashboardVisibility::Personal->value,
            'category' => AnalyticsCategory::Business->value,
            'is_default' => true,
        ])->assertCreated()
            ->assertJsonPath('data.dashboard.visibility', 'personal')
            ->assertJsonPath('data.dashboard.is_default', true);

        $this->assertSame($this->admin->id, $personal->json('data.dashboard.owner_id'));

        $this->postJson('/api/v1/analytics/dashboards', [
            'name' => 'Company Ops Board',
            'visibility' => AnalyticsDashboardVisibility::Company->value,
            'category' => AnalyticsCategory::Operational->value,
            'status' => 'published',
        ])->assertCreated()
            ->assertJsonPath('data.dashboard.visibility', 'company');

        $this->getJson('/api/v1/analytics/dashboards?mine=1')
            ->assertOk()
            ->assertJsonPath('data.dashboards.meta.total', 1);
    }

    public function test_designer_can_add_widgets_and_save_layout(): void
    {
        Sanctum::actingAs($this->admin);

        AnalyticsEvent::factory()->count(3)->category(AnalyticsCategory::Operational)->create([
            'occurred_at' => now()->subDay(),
        ]);

        $dashboardUuid = $this->postJson('/api/v1/analytics/dashboards', [
            'name' => 'Designer Board',
            'visibility' => 'personal',
            'category' => 'operational',
        ])->json('data.dashboard.uuid');

        $kpi = $this->postJson("/api/v1/analytics/dashboards/{$dashboardUuid}/widgets", [
            'name' => 'Ops KPI',
            'type' => AnalyticsWidgetType::Kpi->value,
            'position_x' => 0,
            'position_y' => 0,
            'width' => 3,
            'height' => 2,
        ])->assertCreated()->json('data.widget');

        $map = $this->postJson("/api/v1/analytics/dashboards/{$dashboardUuid}/widgets", [
            'name' => 'Regions',
            'type' => AnalyticsWidgetType::Map->value,
            'position_x' => 3,
            'position_y' => 0,
            'width' => 6,
            'height' => 3,
        ])->assertCreated()->json('data.widget');

        $this->putJson("/api/v1/analytics/dashboards/{$dashboardUuid}/layout", [
            'layout' => ['columns' => 12, 'row_height' => 90, 'gap' => 12],
            'widgets' => [
                [
                    'uuid' => $kpi['uuid'],
                    'position_x' => 0,
                    'position_y' => 1,
                    'width' => 4,
                    'height' => 2,
                    'sort_order' => 0,
                ],
                [
                    'uuid' => $map['uuid'],
                    'position_x' => 4,
                    'position_y' => 1,
                    'width' => 8,
                    'height' => 4,
                    'sort_order' => 1,
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.dashboard.layout.row_height', 90);

        $this->assertDatabaseHas('analytics_widgets', [
            'uuid' => $kpi['uuid'],
            'position_x' => 0,
            'position_y' => 1,
            'width' => 4,
        ]);

        $this->getJson("/api/v1/analytics/dashboards/{$dashboardUuid}/data")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_templates_and_create_from_template(): void
    {
        Sanctum::actingAs($this->admin);

        $template = AnalyticsDashboard::factory()->template()->create([
            'name' => 'Ops Template',
            'slug' => 'ops-template-test',
        ]);

        AnalyticsWidget::factory()->forDashboard($template)->create([
            'name' => 'Template KPI',
            'type' => AnalyticsWidgetType::Kpi->value,
            'key' => 'template_kpi',
        ]);

        $this->getJson('/api/v1/analytics/dashboards/templates')
            ->assertOk()
            ->assertJsonPath('success', true);

        $created = $this->postJson("/api/v1/analytics/dashboards/{$template->uuid}/from-template", [
            'name' => 'My Ops From Template',
            'visibility' => 'personal',
        ])->assertCreated()
            ->assertJsonPath('data.dashboard.name', 'My Ops From Template')
            ->assertJsonPath('data.dashboard.is_template', false);

        $this->assertSame($template->id, $created->json('data.dashboard.template_source_id'));
        $this->assertDatabaseCount('analytics_widgets', 2);
    }

    public function test_dashboard_sharing_with_role(): void
    {
        Sanctum::actingAs($this->admin);

        $role = Role::findByName('manager', 'web');

        $dashboardUuid = $this->postJson('/api/v1/analytics/dashboards', [
            'name' => 'Shared Role Board',
            'visibility' => 'personal',
        ])->json('data.dashboard.uuid');

        $this->postJson("/api/v1/analytics/dashboards/{$dashboardUuid}/shares", [
            'share_type' => 'role',
            'share_id' => $role->id,
            'can_edit' => false,
        ])->assertCreated()
            ->assertJsonPath('data.share.share_type', 'role');

        $this->getJson("/api/v1/analytics/dashboards/{$dashboardUuid}/shares")
            ->assertOk()
            ->assertJsonPath('data.shares.0.share_id', $role->id);

        $shareUuid = $this->getJson("/api/v1/analytics/dashboards/{$dashboardUuid}/shares")
            ->json('data.shares.0.uuid');

        $this->deleteJson("/api/v1/analytics/dashboards/{$dashboardUuid}/shares/{$shareUuid}")
            ->assertOk();
    }
}
