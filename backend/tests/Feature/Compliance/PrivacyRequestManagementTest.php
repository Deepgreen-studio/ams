<?php

namespace Tests\Feature\Compliance;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Customers\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrivacyRequestManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'privacy-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Privacy Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_guest_cannot_list_privacy_requests(): void
    {
        $this->getJson('/api/v1/compliance/privacy-requests')->assertUnauthorized();
    }

    public function test_admin_can_create_list_and_view_privacy_request(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/compliance/privacy-requests', [
            'company_id' => $this->company->uuid,
            'request_type' => 'access_request',
            'requester_name' => 'Jane Subject',
            'requester_email' => 'jane@example.com',
            'description' => 'Please provide a copy of my personal data.',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.privacy_request.request_type', 'access_request')
            ->assertJsonPath('data.privacy_request.requester_name', 'Jane Subject')
            ->assertJsonPath('data.privacy_request.status', 'submitted');

        $uuid = $create->json('data.privacy_request.uuid');
        $this->assertNotEmpty($create->json('data.privacy_request.request_number'));

        $this->getJson('/api/v1/compliance/privacy-requests?search=Jane')
            ->assertOk()
            ->assertJsonPath('data.privacy_requests.meta.total', 1);

        $this->getJson('/api/v1/compliance/privacy-requests/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.privacy_request.company.uuid', $this->company->uuid);

        $this->getJson('/api/v1/compliance/privacy-requests/'.$uuid.'/timeline')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_validation_rejects_invalid_privacy_request(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/compliance/privacy-requests', [
            'company_id' => '',
            'request_type' => 'invalid',
            'requester_name' => '',
            'requester_email' => 'not-an-email',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['company_id', 'request_type', 'requester_name', 'requester_email']]);
    }

    public function test_identity_verification_and_approval_workflow(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/compliance/privacy-requests', [
            'company_id' => $this->company->uuid,
            'request_type' => 'data_export',
            'requester_name' => 'Export Subject',
            'requester_email' => 'export@example.com',
        ])->assertCreated();

        $uuid = $create->json('data.privacy_request.uuid');

        $this->postJson('/api/v1/compliance/privacy-requests/'.$uuid.'/approve', [
            'notes' => 'Trying early',
        ])->assertStatus(422);

        $this->postJson('/api/v1/compliance/privacy-requests/'.$uuid.'/verify-identity', [
            'verified' => true,
            'notes' => 'Passport matched',
        ])
            ->assertOk()
            ->assertJsonPath('data.privacy_request.identity_verification_status', 'verified')
            ->assertJsonPath('data.privacy_request.status', 'under_review');

        $this->postJson('/api/v1/compliance/privacy-requests/'.$uuid.'/approve', [
            'decision' => 'approved',
            'notes' => 'Approved after verification',
        ])
            ->assertOk()
            ->assertJsonPath('data.privacy_request.status', 'approved')
            ->assertJsonPath('data.privacy_request.decision', 'approved');
    }

    public function test_export_and_complete_workflow(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->admin);

        $request = PrivacyRequest::factory()->forCompany($this->company)->create([
            'request_type' => 'data_portability',
            'status' => 'approved',
            'identity_verification_status' => 'verified',
            'identity_verified_at' => now(),
            'decision' => 'approved',
            'decision_at' => now(),
        ]);

        $this->postJson('/api/v1/compliance/privacy-requests/'.$request->uuid.'/export')
            ->assertOk()
            ->assertJsonPath('data.privacy_request.has_export', true)
            ->assertJsonPath('data.privacy_request.status', 'in_progress');

        $this->assertNotNull($request->fresh()->export_file_path);
        Storage::disk('local')->assertExists($request->fresh()->export_file_path);

        $this->postJson('/api/v1/compliance/privacy-requests/'.$request->uuid.'/complete', [
            'notes' => 'Package delivered',
        ])
            ->assertOk()
            ->assertJsonPath('data.privacy_request.status', 'completed');
    }

    public function test_data_deletion_confirmation_workflow(): void
    {
        Sanctum::actingAs($this->admin);

        $request = PrivacyRequest::factory()->forCompany($this->company)->create([
            'request_type' => 'data_deletion',
            'status' => 'approved',
            'identity_verification_status' => 'verified',
            'identity_verified_at' => now(),
            'decision' => 'approved',
            'decision_at' => now(),
        ]);

        $this->postJson('/api/v1/compliance/privacy-requests/'.$request->uuid.'/complete')
            ->assertStatus(422);

        $this->postJson('/api/v1/compliance/privacy-requests/'.$request->uuid.'/confirm-deletion', [
            'confirmed' => true,
            'notes' => 'Records purged from CRM',
        ])
            ->assertOk()
            ->assertJsonPath('data.privacy_request.status', 'in_progress');

        $this->assertNotNull($request->fresh()->deletion_confirmed_at);

        $this->postJson('/api/v1/compliance/privacy-requests/'.$request->uuid.'/complete')
            ->assertOk()
            ->assertJsonPath('data.privacy_request.status', 'completed');
    }

    public function test_reject_requires_notes(): void
    {
        Sanctum::actingAs($this->admin);

        $request = PrivacyRequest::factory()->forCompany($this->company)->verified()->create([
            'request_type' => 'consent_withdrawal',
            'status' => 'under_review',
        ]);

        $this->postJson('/api/v1/compliance/privacy-requests/'.$request->uuid.'/reject', [])
            ->assertStatus(422);

        $this->postJson('/api/v1/compliance/privacy-requests/'.$request->uuid.'/reject', [
            'notes' => 'Unable to verify lawful basis for request',
        ])
            ->assertOk()
            ->assertJsonPath('data.privacy_request.status', 'rejected')
            ->assertJsonPath('data.privacy_request.decision', 'rejected');
    }

    public function test_privacy_dashboard_and_permissions(): void
    {
        $officer = User::factory()->create(['email' => 'privacy-officer@example.com']);
        $officer->assignRole('compliance-officer');
        Sanctum::actingAs($officer);

        PrivacyRequest::factory()->forCompany($this->company)->create([
            'request_type' => 'right_to_object',
            'status' => 'submitted',
            'identity_verification_status' => 'pending',
        ]);

        $this->getJson('/api/v1/compliance/privacy-requests/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'statistics' => ['total', 'active', 'awaiting_verification'],
                    'recent_active',
                    'awaiting_verification',
                ],
            ]);

        $forbidden = User::factory()->create();
        Sanctum::actingAs($forbidden);
        $this->getJson('/api/v1/compliance/privacy-requests')->assertForbidden();
    }

    public function test_customer_must_belong_to_company(): void
    {
        Sanctum::actingAs($this->admin);

        $otherCompany = Company::query()->create([
            'company_name' => 'Other Privacy Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $customer = Customer::query()->create([
            'company_id' => $otherCompany->id,
            'customer_type' => 'individual',
            'first_name' => 'Foreign',
            'last_name' => 'Customer',
            'email' => 'foreign@example.com',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/compliance/privacy-requests', [
            'company_id' => $this->company->uuid,
            'customer_id' => $customer->uuid,
            'request_type' => 'data_correction',
            'requester_name' => 'Foreign Customer',
            'requester_email' => 'foreign@example.com',
        ])->assertStatus(422);
    }
}
