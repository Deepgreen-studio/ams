<?php

namespace Tests\Feature\Customers;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerDocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        Storage::fake('public');

        $this->admin = User::factory()->create(['email' => 'docs-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Docs Tenant',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::factory()->business()->forCompany($this->company)->create([
            'email' => 'docs-buyer@example.com',
            'company_name' => 'Docs Buyer',
        ]);
    }

    public function test_guest_cannot_list_documents(): void
    {
        $this->getJson('/api/v1/customer-documents')->assertUnauthorized();
    }

    public function test_admin_can_upload_list_preview_and_download_document(): void
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->create('msa-contract.pdf', 120, 'application/pdf');

        $create = $this->post('/api/v1/customer-documents', [
            'customer_id' => $this->customer->uuid,
            'name' => 'Master Service Agreement',
            'category' => 'contracts',
            'status' => 'active',
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.document.category', 'contracts')
            ->assertJsonPath('data.document.name', 'Master Service Agreement')
            ->assertJsonPath('data.document.version', 1)
            ->assertJsonPath('data.document.is_current', true);

        $uuid = $create->json('data.document.uuid');
        $path = CustomerDocument::query()->where('uuid', $uuid)->value('path');
        Storage::disk('public')->assertExists($path);

        $this->getJson('/api/v1/customer-documents?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.documents.meta.total', 1)
            ->assertJsonPath('data.statistics.total', 1)
            ->assertJsonPath('data.folders.0.category', 'contracts');

        $this->getJson('/api/v1/customer-documents/folders?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonStructure(['data' => ['folders']]);

        $this->get('/api/v1/customer-documents/'.$uuid.'/download')
            ->assertOk();

        $this->get('/api/v1/customer-documents/'.$uuid.'/preview')
            ->assertOk();
    }

    public function test_admin_can_upload_version_and_view_history(): void
    {
        Sanctum::actingAs($this->admin);

        $first = $this->post('/api/v1/customer-documents', [
            'customer_id' => $this->customer->uuid,
            'name' => 'NDA',
            'category' => 'nda',
            'file' => UploadedFile::fake()->create('nda-v1.pdf', 80, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $uuid = $first->json('data.document.uuid');

        $second = $this->post('/api/v1/customer-documents/'.$uuid.'/versions', [
            'file' => UploadedFile::fake()->create('nda-v2.pdf', 90, 'application/pdf'),
            'name' => 'NDA Updated',
        ], ['Accept' => 'application/json']);

        $second->assertCreated()
            ->assertJsonPath('data.document.version', 2)
            ->assertJsonPath('data.document.is_current', true)
            ->assertJsonPath('data.document.name', 'NDA Updated');

        $this->assertFalse(CustomerDocument::query()->where('uuid', $uuid)->value('is_current'));

        $this->getJson('/api/v1/customer-documents/'.$second->json('data.document.uuid').'/versions')
            ->assertOk()
            ->assertJsonCount(2, 'data.versions');

        $this->getJson('/api/v1/customer-documents?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.documents.meta.total', 1);
    }

    public function test_admin_can_update_archive_and_restore_document(): void
    {
        Sanctum::actingAs($this->admin);

        $document = CustomerDocument::factory()->forCustomer($this->customer)->create([
            'category' => 'invoices',
            'name' => 'Invoice 1001',
            'path' => 'customer-documents/demo/invoice.pdf',
            'disk' => 'public',
        ]);
        Storage::disk('public')->put($document->path, 'invoice-content');

        $this->putJson('/api/v1/customer-documents/'.$document->uuid, [
            'name' => 'Invoice 1001 Revised',
            'status' => 'active',
            'category' => 'invoices',
            'notes' => 'Paid',
        ])
            ->assertOk()
            ->assertJsonPath('data.document.name', 'Invoice 1001 Revised');

        $this->deleteJson('/api/v1/customer-documents/'.$document->uuid)->assertOk();
        $this->assertSoftDeleted('customer_documents', ['id' => $document->id]);

        $this->postJson('/api/v1/customer-documents/'.$document->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.document.uuid', $document->uuid)
            ->assertJsonPath('data.document.status', 'active');
    }

    public function test_upload_rejects_disallowed_extension(): void
    {
        Sanctum::actingAs($this->admin);

        $this->post('/api/v1/customer-documents', [
            'customer_id' => $this->customer->uuid,
            'category' => 'attachments',
            'file' => UploadedFile::fake()->create('malware.exe', 20, 'application/octet-stream'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }
}
