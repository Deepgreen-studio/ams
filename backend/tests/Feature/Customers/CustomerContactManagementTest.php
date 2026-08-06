<?php

namespace Tests\Feature\Customers;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerContact;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerContactManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'contact-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $company = Company::query()->create([
            'company_name' => 'Tenant Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::factory()->business()->forCompany($company)->create([
            'email' => 'customer@example.com',
            'company_name' => 'Acme Buyer',
        ]);
    }

    public function test_guest_cannot_list_contacts(): void
    {
        $this->getJson('/api/v1/customer-contacts')->assertUnauthorized();
    }

    public function test_admin_can_create_list_and_view_contact(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/customer-contacts', [
            'customer_id' => $this->customer->uuid,
            'contact_type' => 'technical',
            'name' => 'Alice Tech',
            'email' => 'alice@example.com',
            'phone' => '+1 555 1000',
            'position' => 'CTO',
            'department' => 'IT',
            'status' => 'active',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.contact.name', 'Alice Tech')
            ->assertJsonPath('data.contact.contact_type', 'technical');

        $uuid = $create->json('data.contact.uuid');

        $this->getJson('/api/v1/customer-contacts?customer='.$this->customer->uuid.'&search=Alice')
            ->assertOk()
            ->assertJsonPath('data.contacts.meta.total', 1);

        $this->getJson('/api/v1/customer-contacts/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.contact.email', 'alice@example.com')
            ->assertJsonPath('data.contact.customer.uuid', $this->customer->uuid);
    }

    public function test_only_one_primary_contact_is_kept_per_customer(): void
    {
        Sanctum::actingAs($this->admin);

        $first = $this->postJson('/api/v1/customer-contacts', [
            'customer_id' => $this->customer->uuid,
            'contact_type' => 'primary',
            'name' => 'Primary One',
            'email' => 'primary1@example.com',
        ])->assertCreated()->json('data.contact');

        $second = $this->postJson('/api/v1/customer-contacts', [
            'customer_id' => $this->customer->uuid,
            'contact_type' => 'primary',
            'name' => 'Primary Two',
            'email' => 'primary2@example.com',
        ])->assertCreated()->json('data.contact');

        $this->assertSame('primary', $second['contact_type']);
        $this->assertDatabaseHas('customer_contacts', [
            'uuid' => $first['uuid'],
            'contact_type' => 'support',
        ]);
        $this->assertDatabaseHas('customer_contacts', [
            'uuid' => $second['uuid'],
            'contact_type' => 'primary',
        ]);
    }

    public function test_contact_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/customer-contacts', [
            'customer_id' => $this->customer->uuid,
            'contact_type' => 'invalid',
            'name' => '',
            'email' => 'bad-email',
            'phone' => '12',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['contact_type', 'name', 'email', 'phone']]);
    }

    public function test_admin_can_update_archive_restore_and_view_timeline(): void
    {
        Sanctum::actingAs($this->admin);

        $contact = CustomerContact::factory()->forCustomer($this->customer)->create([
            'contact_type' => 'billing',
            'name' => 'Bill Keeper',
            'email' => 'billing@example.com',
        ]);

        $this->putJson('/api/v1/customer-contacts/'.$contact->uuid, [
            'name' => 'Bill Updated',
            'status' => 'inactive',
        ])
            ->assertOk()
            ->assertJsonPath('data.contact.name', 'Bill Updated');

        $this->getJson('/api/v1/customer-contacts/'.$contact->uuid.'/timeline')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['timeline']]);

        $this->deleteJson('/api/v1/customer-contacts/'.$contact->uuid)
            ->assertOk()
            ->assertJsonPath('message', 'Customer contact archived successfully.');

        $this->assertSoftDeleted('customer_contacts', ['id' => $contact->id]);

        $this->postJson('/api/v1/customer-contacts/'.$contact->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.contact.uuid', $contact->uuid);
    }

    public function test_contacts_can_be_filtered_by_type_and_status(): void
    {
        Sanctum::actingAs($this->admin);

        CustomerContact::factory()->forCustomer($this->customer)->create([
            'contact_type' => 'support',
            'status' => 'active',
            'name' => 'Support A',
            'email' => 'support-a@example.com',
        ]);
        CustomerContact::factory()->forCustomer($this->customer)->create([
            'contact_type' => 'emergency',
            'status' => 'inactive',
            'name' => 'Emergency B',
            'email' => 'emergency-b@example.com',
        ]);

        $this->getJson('/api/v1/customer-contacts?customer='.$this->customer->uuid.'&contact_type=support')
            ->assertOk()
            ->assertJsonPath('data.contacts.meta.total', 1);

        $this->getJson('/api/v1/customer-contacts?customer='.$this->customer->uuid.'&status=inactive')
            ->assertOk()
            ->assertJsonPath('data.contacts.meta.total', 1);
    }

    public function test_manager_without_update_permission_cannot_create_contact(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/customer-contacts', [
            'customer_id' => $this->customer->uuid,
            'contact_type' => 'support',
            'name' => 'Blocked Contact',
        ])->assertForbidden();
    }
}
