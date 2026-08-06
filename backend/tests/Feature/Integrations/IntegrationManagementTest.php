<?php

namespace Tests\Feature\Integrations;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Models\Integration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IntegrationManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'integration-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Integration Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_guest_cannot_list_integrations(): void
    {
        $this->getJson('/api/v1/integrations')->assertUnauthorized();
    }

    public function test_admin_can_create_list_and_view_integration(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/integrations', [
            'company_id' => $this->company->uuid,
            'name' => 'Salesforce CRM',
            'description' => 'Customer sync integration',
            'type' => 'rest_api',
            'authentication_type' => 'oauth2',
            'base_url' => 'https://api.salesforce.example',
            'api_version' => 'v58',
            'timeout' => 45,
            'retry_attempts' => 2,
            'status' => 'active',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.integration.name', 'Salesforce CRM')
            ->assertJsonPath('data.integration.slug', 'salesforce-crm')
            ->assertJsonPath('data.integration.type', 'rest_api')
            ->assertJsonPath('data.integration.authentication_type', 'oauth2')
            ->assertJsonPath('data.integration.health_status', 'unknown');

        $uuid = $create->json('data.integration.uuid');

        $this->getJson('/api/v1/integrations?search=Salesforce')
            ->assertOk()
            ->assertJsonPath('data.integrations.meta.total', 1);

        $this->getJson('/api/v1/integrations/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.integration.base_url', 'https://api.salesforce.example')
            ->assertJsonPath('data.integration.company.uuid', $this->company->uuid);
    }

    public function test_integration_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/integrations', [
            'company_id' => '',
            'name' => '',
            'type' => 'soap',
            'authentication_type' => 'digest',
            'base_url' => 'not-a-url',
            'timeout' => 999,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['company_id', 'name', 'type', 'authentication_type', 'base_url', 'timeout']]);
    }

    public function test_admin_can_update_soft_delete_and_restore_integration(): void
    {
        Sanctum::actingAs($this->admin);

        $integration = Integration::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Legacy ERP',
            'slug' => 'legacy-erp',
            'type' => 'rest_api',
            'status' => 'draft',
            'authentication_type' => 'api_key',
            'health_status' => 'unknown',
            'timeout' => 30,
            'retry_attempts' => 3,
        ]);

        $this->putJson('/api/v1/integrations/'.$integration->uuid, [
            'name' => 'Legacy ERP Updated',
            'status' => 'inactive',
            'timeout' => 60,
        ])
            ->assertOk()
            ->assertJsonPath('data.integration.name', 'Legacy ERP Updated')
            ->assertJsonPath('data.integration.status', 'inactive')
            ->assertJsonPath('data.integration.timeout', 60);

        $this->deleteJson('/api/v1/integrations/'.$integration->uuid)->assertOk();
        $this->assertSoftDeleted('integrations', ['id' => $integration->id]);

        $this->postJson('/api/v1/integrations/'.$integration->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.integration.uuid', $integration->uuid);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/integrations')->assertForbidden();
    }

    public function test_slug_is_unique_per_company(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/integrations', [
            'company_id' => $this->company->uuid,
            'name' => 'Webhook Listener',
            'slug' => 'webhook-listener',
            'type' => 'webhook',
            'authentication_type' => 'bearer_token',
        ])->assertCreated();

        $second = $this->postJson('/api/v1/integrations', [
            'company_id' => $this->company->uuid,
            'name' => 'Webhook Listener',
            'slug' => 'webhook-listener',
            'type' => 'webhook',
            'authentication_type' => 'bearer_token',
        ])->assertCreated();

        $this->assertSame('webhook-listener-2', $second->json('data.integration.slug'));
    }
}
