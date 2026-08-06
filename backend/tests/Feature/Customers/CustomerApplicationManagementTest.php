<?php

namespace Tests\Feature\Customers;

use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationEnvironment;
use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerApplication;
use App\Domains\Customers\Models\CustomerContact;
use App\Domains\Integrations\Models\Integration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerApplicationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private Customer $customer;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'assignment-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Tenant Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::factory()->business()->forCompany($this->company)->create([
            'email' => 'buyer@example.com',
            'company_name' => 'Buyer Org',
        ]);

        $this->application = Application::factory()->active()->forCompany($this->company)->create([
            'name' => 'Portal App',
            'slug' => 'portal-app',
        ]);
    }

    public function test_guest_cannot_list_assignments(): void
    {
        $this->getJson('/api/v1/customer-applications')->assertUnauthorized();
    }

    public function test_admin_can_assign_list_and_view_application(): void
    {
        Sanctum::actingAs($this->admin);

        $environment = ApplicationEnvironment::query()->create([
            'application_id' => $this->application->id,
            'name' => 'Production',
            'slug' => 'production',
            'type' => 'production',
            'status' => 'active',
            'health_status' => 'unknown',
            'is_current' => true,
        ]);

        $contact = CustomerContact::factory()->forCustomer($this->customer)->create([
            'contact_type' => 'primary',
            'name' => 'Owner Contact',
            'email' => 'owner@example.com',
        ]);

        $create = $this->postJson('/api/v1/customer-applications', [
            'customer_id' => $this->customer->uuid,
            'application_id' => $this->application->uuid,
            'application_environment_id' => $environment->uuid,
            'owner_contact_id' => $contact->uuid,
            'ownership_type' => 'customer_owned',
            'status' => 'active',
            'activated_at' => now()->toISOString(),
            'notes' => 'Initial assignment',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assignment.status', 'active')
            ->assertJsonPath('data.assignment.ownership_type', 'customer_owned')
            ->assertJsonPath('data.assignment.application.uuid', $this->application->uuid)
            ->assertJsonPath('data.assignment.environment.uuid', $environment->uuid)
            ->assertJsonPath('data.assignment.owner_contact.uuid', $contact->uuid);

        $uuid = $create->json('data.assignment.uuid');

        $this->getJson('/api/v1/customer-applications?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.assignments.meta.total', 1);

        $this->getJson('/api/v1/customer-applications/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.assignment.customer.uuid', $this->customer->uuid);
    }

    public function test_customer_can_have_multiple_applications(): void
    {
        Sanctum::actingAs($this->admin);

        $secondApp = Application::factory()->active()->forCompany($this->company)->create([
            'name' => 'Second App',
            'slug' => 'second-app',
        ]);

        $this->postJson('/api/v1/customer-applications', [
            'customer_id' => $this->customer->uuid,
            'application_id' => $this->application->uuid,
            'status' => 'active',
        ])->assertCreated();

        $this->postJson('/api/v1/customer-applications', [
            'customer_id' => $this->customer->uuid,
            'application_id' => $secondApp->uuid,
            'status' => 'pending',
            'ownership_type' => 'platform_managed',
        ])->assertCreated();

        $this->getJson('/api/v1/customer-applications?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.assignments.meta.total', 2);
    }

    public function test_duplicate_assignment_is_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/customer-applications', [
            'customer_id' => $this->customer->uuid,
            'application_id' => $this->application->uuid,
        ])->assertCreated();

        $this->postJson('/api/v1/customer-applications', [
            'customer_id' => $this->customer->uuid,
            'application_id' => $this->application->uuid,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_assignment_rejects_cross_company_application(): void
    {
        Sanctum::actingAs($this->admin);

        $otherCompany = Company::query()->create([
            'company_name' => 'Other Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $foreignApp = Application::factory()->active()->forCompany($otherCompany)->create([
            'name' => 'Foreign App',
            'slug' => 'foreign-app',
        ]);

        $this->postJson('/api/v1/customer-applications', [
            'customer_id' => $this->customer->uuid,
            'application_id' => $foreignApp->uuid,
        ])->assertStatus(422);
    }

    public function test_admin_can_update_archive_restore_history_and_timeline(): void
    {
        Sanctum::actingAs($this->admin);

        $assignment = CustomerApplication::factory()->forCustomer($this->customer)->create([
            'application_id' => $this->application->id,
            'status' => 'pending',
            'ownership_type' => 'shared',
        ]);

        $this->putJson('/api/v1/customer-applications/'.$assignment->uuid, [
            'status' => 'active',
            'ownership_type' => 'customer_owned',
            'expires_at' => now()->addYear()->toISOString(),
        ])
            ->assertOk()
            ->assertJsonPath('data.assignment.status', 'active');

        $this->getJson('/api/v1/customer-applications/'.$assignment->uuid.'/timeline')
            ->assertOk()
            ->assertJsonStructure(['data' => ['timeline']]);

        $this->deleteJson('/api/v1/customer-applications/'.$assignment->uuid)
            ->assertOk();

        $this->assertSoftDeleted('customer_applications', ['id' => $assignment->id]);

        $this->getJson('/api/v1/customer-applications/history?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.history.meta.total', 1);

        $this->postJson('/api/v1/customer-applications/'.$assignment->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.assignment.uuid', $assignment->uuid);
    }

    public function test_integration_defaults_from_application_when_omitted(): void
    {
        Sanctum::actingAs($this->admin);

        $integration = Integration::query()->create([
            'company_id' => $this->company->id,
            'name' => 'CRM Sync',
            'slug' => 'crm-sync',
            'type' => 'rest_api',
            'status' => 'active',
            'authentication_type' => 'api_key',
            'health_status' => 'unknown',
            'timeout' => 30,
            'retry_attempts' => 3,
        ]);

        $this->application->update(['integration_id' => $integration->id]);

        $this->postJson('/api/v1/customer-applications', [
            'customer_id' => $this->customer->uuid,
            'application_id' => $this->application->uuid,
            'status' => 'active',
        ])
            ->assertCreated()
            ->assertJsonPath('data.assignment.integration.uuid', $integration->uuid);
    }

    public function test_manager_without_update_cannot_assign_application(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/customer-applications', [
            'customer_id' => $this->customer->uuid,
            'application_id' => $this->application->uuid,
        ])->assertForbidden();
    }
}
