<?php

namespace Tests\Feature\Integrations;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Models\Integration;
use App\Domains\Integrations\Models\SyncConfig;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'sync-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Sync Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->integration = Integration::query()->create([
            'company_id' => $this->company->id,
            'name' => 'CRM Sync API',
            'slug' => 'crm-sync-api',
            'type' => 'rest_api',
            'status' => 'active',
            'authentication_type' => 'bearer_token',
            'base_url' => 'https://api.sync.example',
            'timeout' => 30,
            'retry_attempts' => 1,
            'credentials' => ['bearer_token' => 'token'],
            'health_status' => 'unknown',
        ]);
    }

    public function test_admin_can_create_sync_config_and_run_sample_import(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/sync/configs', [
            'company_id' => $this->company->uuid,
            'integration_id' => $this->integration->uuid,
            'name' => 'Contacts Import',
            'direction' => 'import',
            'default_mode' => 'full',
            'trigger_type' => 'manual',
            'conflict_strategy' => 'overwrite',
            'source_path' => '/contacts',
            'options' => [
                'sample_records' => [
                    ['id' => 'c1', 'name' => 'Ada'],
                    ['id' => 'c2', 'name' => 'Grace'],
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.config.name', 'Contacts Import');

        $uuid = $create->json('data.config.uuid');

        $run = $this->postJson('/api/v1/sync/configs/'.$uuid.'/run', [
            'mode' => 'full',
            'background' => false,
        ])->assertOk();

        $this->assertSame('completed', $run->json('data.run.status'));
        $this->assertSame(2, $run->json('data.run.total_records'));
        $this->assertSame(2, $run->json('data.run.imported'));
        $this->assertSame(100, $run->json('data.run.progress_percent'));
    }

    public function test_incremental_sync_skips_unchanged_records(): void
    {
        Sanctum::actingAs($this->admin);

        $config = SyncConfig::query()->create([
            'company_id' => $this->company->id,
            'integration_id' => $this->integration->id,
            'name' => 'Incremental Sync',
            'slug' => 'incremental-sync',
            'direction' => 'import',
            'default_mode' => 'incremental',
            'trigger_type' => 'automatic',
            'is_enabled' => true,
            'source_path' => '/items',
            'conflict_strategy' => 'skip',
            'options' => [
                'sample_records' => [
                    ['id' => '1', 'name' => 'One'],
                    ['id' => '2', 'name' => 'Two'],
                ],
            ],
        ]);

        $this->postJson('/api/v1/sync/configs/'.$config->uuid.'/run', ['background' => false])
            ->assertOk()
            ->assertJsonPath('data.run.imported', 2);

        $second = $this->postJson('/api/v1/sync/configs/'.$config->uuid.'/run', ['background' => false])
            ->assertOk();

        $this->assertSame(2, $second->json('data.run.skipped'));
        $this->assertSame(0, $second->json('data.run.imported'));
    }

    public function test_remote_import_uses_api_client(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'api.sync.example/*' => Http::response([
                'data' => [
                    ['id' => 'r1', 'title' => 'Remote'],
                ],
            ], 200),
        ]);

        $config = SyncConfig::query()->create([
            'company_id' => $this->company->id,
            'integration_id' => $this->integration->id,
            'name' => 'Remote Import',
            'slug' => 'remote-import',
            'direction' => 'import',
            'default_mode' => 'full',
            'trigger_type' => 'manual',
            'is_enabled' => true,
            'source_path' => '/v1/records',
            'conflict_strategy' => 'overwrite',
            'options' => [],
        ]);

        $this->postJson('/api/v1/sync/configs/'.$config->uuid.'/run', ['background' => false])
            ->assertOk()
            ->assertJsonPath('data.run.status', 'completed')
            ->assertJsonPath('data.run.imported', 1);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/records'));
    }

    public function test_dashboard_and_history_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        $config = SyncConfig::query()->create([
            'company_id' => $this->company->id,
            'integration_id' => $this->integration->id,
            'name' => 'Dash Sync',
            'slug' => 'dash-sync',
            'direction' => 'import',
            'default_mode' => 'full',
            'trigger_type' => 'manual',
            'is_enabled' => true,
            'options' => [
                'sample_records' => [['id' => 'x', 'name' => 'X']],
            ],
        ]);

        $runUuid = $this->postJson('/api/v1/sync/configs/'.$config->uuid.'/run', ['background' => false])
            ->json('data.run.uuid');

        $this->getJson('/api/v1/sync/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.total_runs', 1);

        $this->getJson('/api/v1/sync/runs')
            ->assertOk()
            ->assertJsonPath('data.runs.meta.total', 1);

        $this->getJson('/api/v1/sync/logs?sync_run='.$runUuid)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertGreaterThan(0, $this->getJson('/api/v1/sync/logs?sync_run='.$runUuid)->json('data.logs.meta.total'));
    }

    public function test_disabled_config_cannot_run(): void
    {
        Sanctum::actingAs($this->admin);

        $config = SyncConfig::query()->create([
            'company_id' => $this->company->id,
            'integration_id' => $this->integration->id,
            'name' => 'Disabled',
            'slug' => 'disabled',
            'direction' => 'import',
            'default_mode' => 'full',
            'trigger_type' => 'manual',
            'is_enabled' => false,
            'options' => ['sample_records' => [['id' => 1]]],
        ]);

        $this->postJson('/api/v1/sync/configs/'.$config->uuid.'/run')
            ->assertStatus(422);
    }
}
