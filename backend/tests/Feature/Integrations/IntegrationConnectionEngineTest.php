<?php

namespace Tests\Feature\Integrations;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Models\Integration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IntegrationConnectionEngineTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'connection-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Connection Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->integration = Integration::query()->create([
            'company_id' => $this->company->id,
            'name' => 'External API',
            'slug' => 'external-api',
            'type' => 'rest_api',
            'status' => 'active',
            'authentication_type' => 'bearer_token',
            'base_url' => 'https://api.example.test',
            'health_check_path' => '/health',
            'timeout' => 15,
            'retry_attempts' => 2,
            'rate_limit_per_minute' => 60,
            'default_headers' => ['X-Client' => 'AMS'],
            'credentials' => ['bearer_token' => 'secret-token-123'],
            'health_status' => 'unknown',
        ]);
    }

    public function test_admin_can_update_api_configuration(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/integrations/'.$this->integration->uuid.'/configuration', [
            'base_url' => 'https://api.updated.test',
            'timeout' => 45,
            'retry_attempts' => 3,
            'health_check_path' => '/status',
            'default_headers' => ['X-Env' => 'test'],
            'default_query' => ['lang' => 'en'],
            'rate_limit_per_minute' => 120,
            'credentials' => [
                'bearer_token' => 'new-token',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.integration.base_url', 'https://api.updated.test')
            ->assertJsonPath('data.integration.has_credentials', true)
            ->assertJsonMissingPath('data.integration.credentials');

        $this->integration->refresh();
        $this->assertSame('new-token', $this->integration->credentials['bearer_token']);
    }

    public function test_connection_test_updates_health_and_history(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'api.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->postJson('/api/v1/integrations/'.$this->integration->uuid.'/test-connection')
            ->assertOk()
            ->assertJsonPath('data.response.successful', true)
            ->assertJsonPath('data.integration.health_status', 'healthy');

        $this->assertNotNull($response->json('data.log.uuid'));
        $this->assertDatabaseHas('integration_connection_logs', [
            'integration_id' => $this->integration->id,
            'request_type' => 'connection_test',
            'success' => 1,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.test/health'
                && ! $request->hasHeader('Authorization');
        });
    }

    public function test_authentication_test_sends_bearer_token(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'api.example.test/*' => Http::response(['auth' => true], 200),
        ]);

        $this->postJson('/api/v1/integrations/'.$this->integration->uuid.'/test-authentication')
            ->assertOk()
            ->assertJsonPath('data.response.successful', true);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer secret-token-123');
        });
    }

    public function test_request_tester_supports_json_body_and_query(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'api.example.test/*' => Http::response(['created' => true], 201),
        ]);

        $this->postJson('/api/v1/integrations/'.$this->integration->uuid.'/execute', [
            'method' => 'POST',
            'path' => '/v1/resources',
            'headers' => ['X-Trace' => 'abc'],
            'query' => ['dry_run' => '1'],
            'body' => ['name' => 'Demo'],
            'apply_auth' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.response.status_code', 201)
            ->assertJsonPath('data.log.request_type', 'request');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v1/resources')
                && $request['name'] === 'Demo'
                && $request->hasHeader('X-Trace', 'abc')
                && $request->hasHeader('Authorization');
        });
    }

    public function test_request_tester_supports_file_upload(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'api.example.test/*' => Http::response(['uploaded' => true], 200),
        ]);

        $this->post('/api/v1/integrations/'.$this->integration->uuid.'/execute', [
            'method' => 'POST',
            'path' => '/upload',
            'apply_auth' => false,
            'file' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertOk()->assertJsonPath('data.log.request_type', 'upload');
    }

    public function test_connection_history_is_listable(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'api.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        $this->postJson('/api/v1/integrations/'.$this->integration->uuid.'/test-connection')->assertOk();

        $this->getJson('/api/v1/integrations/'.$this->integration->uuid.'/history')
            ->assertOk()
            ->assertJsonPath('data.history.meta.total', 1);
    }

    public function test_history_masks_authorization_header(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'api.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        $result = $this->postJson('/api/v1/integrations/'.$this->integration->uuid.'/test-authentication')
            ->assertOk()
            ->json('data.log');

        $this->assertSame('***MASKED***', $result['request_headers']['Authorization'] ?? null);
    }

    public function test_user_without_manage_cannot_test_connection(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('integrations.view');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/integrations/'.$this->integration->uuid.'/test-connection')
            ->assertForbidden();
    }
}
