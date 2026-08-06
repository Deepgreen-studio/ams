<?php

namespace Tests\Feature\Customers;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'customer-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Tenant Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_guest_cannot_list_customers(): void
    {
        $this->getJson('/api/v1/customers')->assertUnauthorized();
    }

    public function test_admin_can_create_list_and_view_customer(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'phone' => '+1 555 0100',
            'country' => 'US',
            'status' => 'active',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer.first_name', 'Jane')
            ->assertJsonPath('data.customer.display_name', 'Jane Doe')
            ->assertJsonPath('data.customer.customer_type', 'individual');

        $uuid = $create->json('data.customer.uuid');

        $this->getJson('/api/v1/customers?search=Jane')
            ->assertOk()
            ->assertJsonPath('data.customers.meta.total', 1)
            ->assertJsonPath('data.statistics.total', 1)
            ->assertJsonPath('data.statistics.individual', 1);

        $this->getJson('/api/v1/customers/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.customer.email', 'jane.doe@example.com')
            ->assertJsonPath('data.customer.company.uuid', $this->company->uuid);
    }

    public function test_admin_can_create_business_and_enterprise_customers(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'business',
            'company_name' => 'Acme Retail',
            'email' => 'ops@acme-retail.test',
            'industry' => 'Retail',
        ])
            ->assertCreated()
            ->assertJsonPath('data.customer.display_name', 'Acme Retail')
            ->assertJsonPath('data.customer.customer_type', 'business');

        $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'enterprise',
            'company_name' => 'Globex Holdings',
            'email' => 'contact@globex.test',
            'industry' => 'Technology',
        ])
            ->assertCreated()
            ->assertJsonPath('data.customer.customer_type', 'enterprise');
    }

    public function test_customer_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'individual',
            'email' => 'not-an-email',
            'website' => 'not-a-url',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email', 'website', 'first_name', 'last_name']]);

        $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'business',
            'email' => 'biz@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['company_name']]);
    }

    public function test_admin_can_update_archive_and_restore_customer(): void
    {
        Sanctum::actingAs($this->admin);

        $customer = Customer::factory()->individual()->forCompany($this->company)->create([
            'email' => 'archive.me@example.com',
            'status' => 'active',
        ]);

        $this->putJson('/api/v1/customers/'.$customer->uuid, [
            'first_name' => 'Updated',
            'last_name' => 'Person',
            'status' => 'inactive',
        ])
            ->assertOk()
            ->assertJsonPath('data.customer.first_name', 'Updated')
            ->assertJsonPath('data.customer.status', 'inactive');

        $this->deleteJson('/api/v1/customers/'.$customer->uuid)
            ->assertOk()
            ->assertJsonPath('message', 'Customer archived successfully.');

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);

        $this->postJson('/api/v1/customers/'.$customer->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.customer.uuid', $customer->uuid);
    }

    public function test_email_must_be_unique_within_company(): void
    {
        Sanctum::actingAs($this->admin);

        Customer::factory()->individual()->forCompany($this->company)->create([
            'email' => 'shared@example.com',
        ]);

        $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'individual',
            'first_name' => 'Dup',
            'last_name' => 'Email',
            'email' => 'shared@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_customers_can_be_filtered_by_company_and_type(): void
    {
        Sanctum::actingAs($this->admin);

        $otherCompany = Company::query()->create([
            'company_name' => 'Other Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        Customer::factory()->individual()->forCompany($this->company)->create(['email' => 'a@example.com']);
        Customer::factory()->business()->forCompany($this->company)->create(['email' => 'b@example.com']);
        Customer::factory()->enterprise()->forCompany($otherCompany)->create(['email' => 'c@example.com']);

        $this->getJson('/api/v1/customers?company='.$this->company->uuid)
            ->assertOk()
            ->assertJsonPath('data.customers.meta.total', 2);

        $this->getJson('/api/v1/customers?customer_type=business&company='.$this->company->uuid)
            ->assertOk()
            ->assertJsonPath('data.customers.meta.total', 1);
    }

    public function test_manager_without_create_permission_cannot_create_customer(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'individual',
            'first_name' => 'Blocked',
            'last_name' => 'User',
            'email' => 'blocked@example.com',
        ])->assertForbidden();
    }
}
