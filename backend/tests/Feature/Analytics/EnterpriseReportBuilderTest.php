<?php

namespace Tests\Feature\Analytics;

use App\Domains\Analytics\Enums\AnalyticsCategory;
use App\Domains\Analytics\Enums\AnalyticsReportFormat;
use App\Domains\Analytics\Enums\AnalyticsReportType;
use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Domains\Analytics\Models\AnalyticsReport;
use App\Domains\Analytics\Models\AnalyticsReportRun;
use App\Domains\Scheduler\Enums\ScheduledJobHandler;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnterpriseReportBuilderTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'report-builder@example.com']);
        $this->admin->assignRole('super-admin');
        Storage::fake('local');
    }

    public function test_can_create_designer_preview_and_export_reports(): void
    {
        Sanctum::actingAs($this->admin);

        AnalyticsEvent::factory()->count(5)->category(AnalyticsCategory::Business)->create([
            'occurred_at' => now()->subDays(1),
            'metrics' => ['count' => 1, 'value' => 10],
        ]);

        $create = $this->postJson('/api/v1/analytics/reports', [
            'name' => 'Business Events Tabular',
            'category' => AnalyticsCategory::Business->value,
            'report_type' => AnalyticsReportType::Tabular->value,
            'status' => 'active',
            'visibility' => 'personal',
            'is_saved' => true,
            'columns' => [
                ['key' => 'occurred_at', 'label' => 'Occurred At'],
                ['key' => 'category', 'label' => 'Category'],
                ['key' => 'event_name', 'label' => 'Event'],
                ['key' => 'metric_count', 'label' => 'Count'],
            ],
            'filters' => [
                'from' => now()->subDays(7)->toDateString(),
                'to' => now()->toDateString(),
                'category' => AnalyticsCategory::Business->value,
            ],
            'sorting' => ['field' => 'occurred_at', 'direction' => 'desc'],
            'format_defaults' => ['format' => 'csv'],
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report.report_type', 'tabular')
            ->assertJsonPath('data.report.is_saved', true);

        $uuid = $create->json('data.report.uuid');

        $this->putJson("/api/v1/analytics/reports/{$uuid}/designer", [
            'grouping' => ['fields' => ['category'], 'aggregate' => 'count'],
            'report_type' => AnalyticsReportType::Grouped->value,
            'chart_config' => ['type' => 'bar'],
        ])->assertOk()
            ->assertJsonPath('data.report.report_type', 'grouped');

        $preview = $this->postJson("/api/v1/analytics/reports/{$uuid}/preview", [
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]);

        $preview->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'dataset' => ['columns', 'rows', 'groups', 'chart', 'meta'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, $preview->json('data.dataset.meta.row_count'));

        foreach ([AnalyticsReportFormat::Csv->value, AnalyticsReportFormat::Excel->value, AnalyticsReportFormat::Pdf->value] as $format) {
            $run = $this->postJson("/api/v1/analytics/reports/{$uuid}/run", [
                'format' => $format,
            ]);

            $run->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.run.format', $format)
                ->assertJsonPath('data.run.status', 'completed');

            $runUuid = $run->json('data.run.uuid');

            $this->get("/api/v1/analytics/reports/{$uuid}/runs/{$runUuid}/download")
                ->assertOk();
        }

        $this->getJson("/api/v1/analytics/reports/{$uuid}/runs")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertGreaterThanOrEqual(3, AnalyticsReportRun::query()->count());
    }

    public function test_can_schedule_saved_report(): void
    {
        Sanctum::actingAs($this->admin);

        $report = AnalyticsReport::factory()->create([
            'name' => 'Scheduled Ops Report',
            'owner_id' => $this->admin->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'status' => 'active',
            'is_saved' => true,
        ]);

        AnalyticsEvent::factory()->count(2)->create(['occurred_at' => now()->subDay()]);

        $response = $this->putJson("/api/v1/analytics/reports/{$report->uuid}/schedule", [
            'enabled' => true,
            'cron' => '0 8 * * *',
            'format' => 'csv',
            'timezone' => 'UTC',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.report.is_scheduled', true);

        $this->assertDatabaseHas('scheduled_jobs', [
            'handler_key' => ScheduledJobHandler::AnalyticsReport->value,
            'is_enabled' => 1,
        ]);

        $job = ScheduledJob::query()->where('handler_key', ScheduledJobHandler::AnalyticsReport->value)->first();
        $this->assertNotNull($job);
        $this->assertSame($report->uuid, $job->payload['report_uuid'] ?? null);

        $this->getJson('/api/v1/analytics/reports?is_saved=1')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_list_includes_builder_metadata(): void
    {
        Sanctum::actingAs($this->admin);

        AnalyticsReport::factory()->count(2)->create([
            'created_by' => $this->admin->id,
            'owner_id' => $this->admin->id,
        ]);

        $this->getJson('/api/v1/analytics/reports')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'reports' => ['items', 'meta'],
                    'column_catalog',
                    'report_types',
                    'formats',
                ],
            ]);
    }
}
