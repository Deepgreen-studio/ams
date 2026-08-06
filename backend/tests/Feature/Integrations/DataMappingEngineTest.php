<?php

namespace Tests\Feature\Integrations;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Models\Integration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DataMappingEngineTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'mapping-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'EasyCarbs Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->integration = Integration::query()->create([
            'company_id' => $this->company->id,
            'name' => 'EasyCarbs API',
            'slug' => 'easycarbs-api',
            'type' => 'rest_api',
            'status' => 'active',
            'authentication_type' => 'bearer_token',
            'base_url' => 'https://api.easycarbs.example',
            'timeout' => 30,
            'retry_attempts' => 1,
            'credentials' => ['bearer_token' => 'token'],
            'health_status' => 'unknown',
        ]);
    }

    public function test_admin_can_create_easycarbs_mapping_profile(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/mappings', [
            'company_id' => $this->company->uuid,
            'integration_id' => $this->integration->uuid,
            'name' => 'EasyCarbs Customers',
            'source_entity' => 'EasyCarbs',
            'target_entity' => 'Users',
            'direction' => 'inbound',
            'status' => 'active',
            'sample_payload' => [
                'customer_name' => 'Ada Lovelace',
                'weight' => '62.5',
            ],
            'fields' => [
                [
                    'external_field' => 'customer_name',
                    'internal_field' => 'Users.first_name',
                    'transform_type' => 'split_first',
                    'transform_config' => ['delimiter' => ' '],
                    'is_required' => true,
                    'sort_order' => 0,
                ],
                [
                    'external_field' => 'weight',
                    'internal_field' => 'Health.weight',
                    'transform_type' => 'cast_float',
                    'is_required' => true,
                    'sort_order' => 1,
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.mapping.name', 'EasyCarbs Customers')
            ->assertJsonPath('data.mapping.source_entity', 'EasyCarbs');

        $this->assertCount(2, $response->json('data.mapping.fields'));
        $this->assertDatabaseCount('data_mappings', 1);
        $this->assertDatabaseCount('data_mapping_fields', 2);
    }

    public function test_preview_transforms_and_nests_output(): void
    {
        Sanctum::actingAs($this->admin);

        $uuid = $this->postJson('/api/v1/mappings', [
            'company_id' => $this->company->uuid,
            'integration_id' => $this->integration->uuid,
            'name' => 'Preview Mapping',
            'source_entity' => 'EasyCarbs',
            'fields' => [
                [
                    'external_field' => 'customer_name',
                    'internal_field' => 'Users.first_name',
                    'transform_type' => 'split_first',
                    'transform_config' => ['delimiter' => ' '],
                    'is_required' => true,
                ],
                [
                    'external_field' => 'weight',
                    'internal_field' => 'Health.weight',
                    'transform_type' => 'cast_float',
                    'is_required' => true,
                ],
            ],
        ])->json('data.mapping.uuid');

        $preview = $this->postJson('/api/v1/mappings/'.$uuid.'/preview', [
            'source' => [
                'customer_name' => 'Ada Lovelace',
                'weight' => '62.5',
            ],
        ])->assertOk()
            ->assertJsonPath('data.result.valid', true)
            ->assertJsonPath('data.result.output.Users.first_name', 'Ada')
            ->assertJsonPath('data.result.output.Health.weight', 62.5);

        $this->assertSame('Ada', $preview->json('data.result.output.Users.first_name'));
    }

    public function test_validation_fails_when_required_missing(): void
    {
        Sanctum::actingAs($this->admin);

        $uuid = $this->postJson('/api/v1/mappings', [
            'company_id' => $this->company->uuid,
            'integration_id' => $this->integration->uuid,
            'name' => 'Required Mapping',
            'source_entity' => 'EasyCarbs',
            'fields' => [
                [
                    'external_field' => 'customer_name',
                    'internal_field' => 'Users.first_name',
                    'is_required' => true,
                ],
            ],
        ])->json('data.mapping.uuid');

        $this->postJson('/api/v1/mappings/'.$uuid.'/validate', [
            'source' => [],
        ])->assertOk()
            ->assertJsonPath('data.valid', false);

        $this->assertNotEmpty($this->postJson('/api/v1/mappings/'.$uuid.'/validate', [
            'source' => [],
        ])->json('data.errors'));
    }

    public function test_default_value_is_applied(): void
    {
        Sanctum::actingAs($this->admin);

        $uuid = $this->postJson('/api/v1/mappings', [
            'company_id' => $this->company->uuid,
            'integration_id' => $this->integration->uuid,
            'name' => 'Defaults Mapping',
            'source_entity' => 'EasyCarbs',
            'fields' => [
                [
                    'external_field' => 'status',
                    'internal_field' => 'Users.status',
                    'transform_type' => 'lowercase',
                    'default_value' => 'active',
                    'is_required' => true,
                ],
            ],
        ])->json('data.mapping.uuid');

        $this->postJson('/api/v1/mappings/'.$uuid.'/preview', [
            'source' => [],
        ])->assertOk()
            ->assertJsonPath('data.result.valid', true)
            ->assertJsonPath('data.result.output.Users.status', 'active');
    }

    public function test_catalogs_endpoint_returns_field_selector_data(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/mappings/catalogs')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'transforms',
                    'internal_fields',
                    'directions',
                    'statuses',
                ],
            ]);
    }

    public function test_custom_rule_rejects_invalid_value(): void
    {
        Sanctum::actingAs($this->admin);

        $uuid = $this->postJson('/api/v1/mappings', [
            'company_id' => $this->company->uuid,
            'integration_id' => $this->integration->uuid,
            'name' => 'Rules Mapping',
            'source_entity' => 'EasyCarbs',
            'fields' => [
                [
                    'external_field' => 'weight',
                    'internal_field' => 'Health.weight',
                    'transform_type' => 'cast_float',
                    'custom_rules' => [
                        ['type' => 'min', 'value' => 10, 'message' => 'Weight too low'],
                    ],
                ],
            ],
        ])->json('data.mapping.uuid');

        $this->postJson('/api/v1/mappings/'.$uuid.'/preview', [
            'source' => ['weight' => 5],
        ])->assertOk()
            ->assertJsonPath('data.result.valid', false)
            ->assertJsonPath('data.result.errors.0', 'Weight too low');
    }
}
