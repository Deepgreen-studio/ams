<?php

namespace Tests\Feature\Customers;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Enums\CustomerPermission;
use App\Domains\Customers\Models\Customer;
use App\Domains\Roles\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            'status' => 'active',
            'legal_basis' => 'contract',
            'processing_purpose' => 'Provide the subscribed application and support.',
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
            'legal_name' => 'Acme Retail',
            'email' => 'ops@acme-retail.test',
            'industry' => 'Retail',
            'legal_basis' => 'contract',
            'processing_purpose' => 'Provide the subscribed application and support.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.customer.display_name', 'Acme Retail')
            ->assertJsonPath('data.customer.customer_type', 'business');

        $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'enterprise',
            'legal_name' => 'Globex Holdings',
            'email' => 'contact@globex.test',
            'industry' => 'Technology',
            'legal_basis' => 'contract',
            'processing_purpose' => 'Provide the subscribed application and support.',
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
            ->assertJsonStructure(['errors' => ['legal_name']]);
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

    public function test_customer_list_actions_follow_their_own_permissions(): void
    {
        $companyAdmin = Role::findByName('company-admin', 'web');
        $this->assertTrue($companyAdmin->hasPermissionTo(CustomerPermission::VIEW));
        $this->assertTrue($companyAdmin->hasPermissionTo(CustomerPermission::CREATE));
        $this->assertTrue($companyAdmin->hasPermissionTo(CustomerPermission::UPDATE));
        $this->assertTrue($companyAdmin->hasPermissionTo(CustomerPermission::DELETE));
        $this->assertTrue($companyAdmin->hasPermissionTo(CustomerPermission::VIEW_TRASH));
        $this->assertTrue($companyAdmin->hasPermissionTo(CustomerPermission::RESTORE));
        $this->assertTrue($companyAdmin->hasPermissionTo(CustomerPermission::FORCE_DELETE));
        $this->assertDatabaseHas('permissions', [
            'name' => CustomerPermission::VIEW_TRASH,
            'display_name' => 'Soft Deleted View',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => CustomerPermission::RESTORE,
            'display_name' => 'Restore Customer',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => CustomerPermission::FORCE_DELETE,
            'display_name' => 'Permanent Delete Customer',
        ]);

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/customers?trashed=only')->assertForbidden();
        $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'individual',
            'first_name' => 'No',
            'last_name' => 'Create',
            'email' => 'no.create@example.com',
        ])->assertForbidden();
    }

    public function test_only_force_delete_permission_can_permanently_delete_a_customer(): void
    {
        $customer = Customer::factory()->individual()->forCompany($this->company)->create([
            'email' => 'gone.forever@example.com',
        ]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson('/api/v1/customers/'.$customer->uuid.'/force-delete')->assertStatus(422);

        $customer->delete();

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);
        $this->deleteJson('/api/v1/customers/'.$customer->uuid.'/force-delete')->assertForbidden();

        Sanctum::actingAs($this->admin);
        $this->deleteJson('/api/v1/customers/'.$customer->uuid.'/force-delete')
            ->assertOk()
            ->assertJsonPath('message', 'Customer permanently deleted.');

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_admin_can_download_an_example_import_customers_and_export_them(): void
    {
        Sanctum::actingAs($this->admin);

        $example = $this->get('/api/v1/customers/example?company='.$this->company->uuid);
        $example->assertOk();
        $example->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('customer_type', $example->streamedContent());
        $this->assertStringContainsString('Tenant Co', $example->streamedContent());
        $this->assertStringContainsString('jane.doe@example.com', $example->streamedContent());

        $csv = implode("\n", [
            'company,customer_type,first_name,last_name,legal_name,email,phone,legal_basis,processing_purpose,status',
            'Tenant Co,individual,Jane,Doe,,jane.doe@example.com,+447700900123,contract,Provide support.,active',
            'Tenant Co,business,,,Northwind Clinic,billing@northwind.example,+447700900456,contract,Subscription billing.,active',
        ]);
        $path = storage_path('framework/testing/customers-import.csv');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $csv);

        $this->post('/api/v1/customers/import', [
            'file' => new UploadedFile($path, 'customers.csv', 'text/csv', null, true),
            'update_existing' => '0',
        ])->assertOk()
            ->assertJsonPath('data.import.created', 2)
            ->assertJsonPath('data.import.skipped', 0);

        $this->assertDatabaseHas('customers', [
            'email' => 'jane.doe@example.com',
            'company_id' => $this->company->id,
        ]);
        $this->assertDatabaseHas('customers', [
            'email' => 'billing@northwind.example',
            'legal_name' => 'Northwind Clinic',
        ]);

        $export = $this->get('/api/v1/customers/export?search=Jane');
        $export->assertOk();
        $this->assertStringContainsString('jane.doe@example.com', $export->streamedContent());

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);
        $this->get('/api/v1/customers/example')->assertForbidden();
        $this->post('/api/v1/customers/import', [
            'file' => new UploadedFile($path, 'customers.csv', 'text/csv', null, true),
        ])->assertForbidden();
    }
}
