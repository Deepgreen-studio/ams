<?php

namespace Tests\Feature\Customers;

use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Models\ConsentType;
use App\Domains\Compliance\Models\UserConsent;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerContact;
use App\Domains\Customers\Models\Industry;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerOperatingModelTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'operating-model-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Owning Co',
            'status' => 'active',
            'country' => 'US',
            'timezone' => 'America/Chicago',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_organization_customer_receives_identity_defaults_and_industry_master(): void
    {
        Sanctum::actingAs($this->admin);

        $technology = Industry::query()->where('code', 'technology')->firstOrFail();
        $software = Industry::query()->where('code', 'software')->firstOrFail();
        $application = Application::factory()->active()->forCompany($this->company)->create([
            'name' => 'Owned App',
            'slug' => 'owned-app',
        ]);

        $response = $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'business',
            'legal_name' => 'Northwind Traders LLC',
            'registration_number' => 'TAX-100',
            'reference' => 'ACCT-100',
            'email' => 'ops@northwind.test',
            'primary_contact_name' => 'Ada Lovelace',
            'primary_contact_email' => 'ada@northwind.test',
            'industry_id' => $technology->uuid,
            'sub_industry_id' => $software->uuid,
            'legal_basis' => 'contract',
            'processing_purpose' => 'Provide the subscribed application and support.',
            'website' => 'https://northwind.test',
            'application' => [
                'application_id' => $application->uuid,
                'ownership_type' => 'platform_managed',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer.customer_type', 'business')
            ->assertJsonPath('data.customer.is_organization', true)
            ->assertJsonPath('data.customer.organization_category', 'business')
            ->assertJsonPath('data.customer.legal_name', 'Northwind Traders LLC')
            ->assertJsonPath('data.customer.display_name', 'Northwind Traders LLC')
            ->assertJsonPath('data.customer.registration_number', 'TAX-100')
            ->assertJsonPath('data.customer.reference', 'ACCT-100')
            ->assertJsonPath('data.customer.industry', 'Technology / Software')
            ->assertJsonPath('data.customer.legal_basis', 'contract');

        $number = $response->json('data.customer.customer_number');
        $this->assertMatchesRegularExpression('/^CUS-\d{8}$/', $number);

        $uuid = $response->json('data.customer.uuid');

        $this->getJson('/api/v1/customers/' . $number)
            ->assertOk()
            ->assertJsonPath('data.customer.uuid', $uuid);

        $this->getJson('/api/v1/customers/' . $uuid . '/console')
            ->assertOk()
            ->assertJsonPath('data.console.model.company_owns_applications', true)
            ->assertJsonPath('data.console.counts.applications', 1)
            ->assertJsonPath('data.console.assignments.0.ownership_type', 'platform_managed')
            ->assertJsonPath('data.console.assignments.0.ownership_description', 'The company operates the application for the customer.');

        $this->assertNotNull($response->json('data.customer.uuid'));
        $this->assertDatabaseHas('customer_applications', [
            'customer_id' => Customer::query()->where('uuid', $uuid)->value('id'),
            'application_id' => $application->id,
            'ownership_type' => 'platform_managed',
        ]);
    }

    public function test_other_industry_requires_controlled_text_and_website_must_be_url(): void
    {
        Sanctum::actingAs($this->admin);
        $other = Industry::query()->where('code', 'other')->firstOrFail();

        $this->postJson('/api/v1/customers', [
            'company_id' => $this->company->uuid,
            'customer_type' => 'enterprise',
            'legal_name' => 'Custom Org',
            'email' => 'custom@example.test',
            'industry_id' => $other->uuid,
            'website' => 'not a url',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['industry_other', 'website']);
    }

    public function test_contact_import_export_respects_permission_and_reports_duplicates(): void
    {
        Sanctum::actingAs($this->admin);

        $customer = Customer::factory()->individual()->forCompany($this->company)->create([
            'email' => 'person@example.test',
        ]);

        $csv = "name,email,phone,contact_type,responsibilities,status\n"
            . "Pat Lee,pat@example.test,+14155552671,technical,technical|security,active\n"
            . "Pat Duplicate,pat@example.test,+14155552671,billing,billing,active\n";

        $import = $this->post('/api/v1/customer-contacts/import', [
            'customer_id' => $customer->uuid,
            'update_existing' => false,
            'file' => UploadedFile::fake()->createWithContent('contacts.csv', $csv),
        ], [
            'Accept' => 'application/json',
        ]);

        $import->assertOk()
            ->assertJsonPath('data.import.created', 1)
            ->assertJsonPath('data.import.skipped', 1);

        $this->get('/api/v1/customer-contacts/export?customer=' . $customer->uuid . '&format=csv')
            ->assertOk();

        $agent = User::factory()->create(['email' => 'support-agent-export@example.com']);
        $agent->assignRole('support-agent');
        Sanctum::actingAs($agent);

        $this->getJson('/api/v1/customer-contacts/export?customer=' . $customer->uuid)
            ->assertForbidden();
    }

    public function test_customer_can_be_anonymized_without_removing_the_record(): void
    {
        Sanctum::actingAs($this->admin);

        $customer = Customer::factory()->individual()->forCompany($this->company)->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.private@example.test',
            'phone' => '+14155552671',
        ]);

        $contact = \App\Domains\Customers\Models\CustomerContact::factory()->forCustomer($customer)->create([
            'name' => 'Jane Doe',
            'email' => 'jane.contact@example.test',
            'notes' => 'Private note',
        ]);
        $note = \App\Domains\Customers\Models\CustomerNote::factory()->forCustomer($customer)->create([
            'title' => 'Private',
            'body' => 'Call Jane at home.',
        ]);

        $this->postJson('/api/v1/customers/' . $customer->uuid . '/anonymize')
            ->assertOk()
            ->assertJsonPath('data.customer.first_name', null)
            ->assertJsonPath('data.customer.status', 'inactive');

        $customer->refresh();
        $this->assertNotNull($customer->anonymized_at);
        $this->assertStringStartsWith('anonymized-', $customer->email);
        $this->assertNotNull($customer->customer_number);
        $this->assertSame('Anonymized contact', $contact->fresh()->name);
        $this->assertNull($contact->fresh()->email);
        $this->assertSame('', $note->fresh()->body);
    }

    public function test_retention_date_anonymizes_the_customer(): void
    {
        $customer = Customer::factory()->individual()->forCompany($this->company)->create([
            'email' => 'expired.retention@example.test',
            'legal_basis' => 'contract',
            'processing_purpose' => 'Provide the subscribed application and support.',
            'retention_until' => now()->subDay()->toDateString(),
        ]);
        $kept = Customer::factory()->individual()->forCompany($this->company)->create([
            'email' => 'still.kept@example.test',
            'legal_basis' => 'legal_obligation',
            'retention_until' => now()->addYear()->toDateString(),
        ]);

        $this->artisan('customers:enforce-retention')->assertSuccessful();

        $this->assertNotNull($customer->fresh()->anonymized_at);
        $this->assertNull($kept->fresh()->anonymized_at);
    }

    public function test_console_privacy_tab_includes_privacy_contact_and_consents(): void
    {
        Sanctum::actingAs($this->admin);

        $customer = Customer::factory()->individual()->forCompany($this->company)->create([
            'email' => 'privacy.console@example.test',
            'legal_basis' => 'consent',
            'processing_purpose' => 'Deliver the service the customer requested.',
        ]);

        CustomerContact::factory()->forCustomer($customer)->create([
            'contact_type' => 'compliance',
            'name' => 'Dana Privacy',
            'email' => 'dana.privacy@example.test',
        ]);

        $type = ConsentType::factory()->forCompany($this->company)->create([
            'name' => 'Product analytics',
            'code' => 'product_analytics_console',
        ]);
        UserConsent::factory()->forType($type)->forCustomer($customer)->create([
            'status' => 'granted',
            'granted' => true,
        ]);

        $this->getJson('/api/v1/customers/'.$customer->uuid.'/console')
            ->assertOk()
            ->assertJsonPath('data.console.privacy.privacy_contacts.0.name', 'Dana Privacy')
            ->assertJsonPath('data.console.privacy.privacy_contacts.0.email', 'dana.privacy@example.test')
            ->assertJsonPath('data.console.privacy.consents.0.consent_type', 'Product analytics')
            ->assertJsonPath('data.console.privacy.consents.0.status', 'granted');
    }
}
