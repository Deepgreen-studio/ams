<?php

namespace Tests\Feature\Support;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Customers\Models\Customer;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportComplianceRoutingTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'routing-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'EasyCarbs Routing Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'GBP',
        ]);

        $this->customer = Customer::query()->create([
            'company_id' => $this->company->id,
            'customer_type' => 'individual',
            'first_name' => 'David',
            'last_name' => 'Test',
            'email' => 'david.routing@example.com',
            'status' => 'active',
        ]);
    }

    public function test_health_information_ticket_auto_routes_to_compliance(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'customer_id' => $this->customer->uuid,
            'subject' => 'Please remove my health information from my account.',
            'description' => 'Please remove my health information from my account.',
            'category' => 'customer_support',
            'priority' => 'high',
            'source' => 'portal',
            'involves_personal_data' => true,
        ]);

        $response->assertCreated();

        $ticket = SupportTicket::query()->where('uuid', $response->json('data.ticket.uuid'))->firstOrFail();
        $this->assertTrue((bool) $ticket->involves_personal_data);
        $this->assertNotNull($ticket->compliance_routed_at);
        $this->assertNotNull($ticket->privacy_request_id);
        $this->assertSame('pending', $ticket->status->value);

        $privacy = PrivacyRequest::query()->findOrFail($ticket->privacy_request_id);
        $this->assertSame($ticket->id, $privacy->support_ticket_id);
        $this->assertSame('data_correction', $privacy->request_type->value);
    }

    public function test_temporary_disable_ticket_stays_in_support(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'customer_id' => $this->customer->uuid,
            'subject' => 'I would like to temporarily disable my account.',
            'description' => 'I would like to temporarily disable my account.',
            'category' => 'customer_support',
            'priority' => 'medium',
            'source' => 'portal',
            'involves_personal_data' => false,
        ]);

        $response->assertCreated();

        $ticket = SupportTicket::query()->where('uuid', $response->json('data.ticket.uuid'))->firstOrFail();
        $this->assertFalse((bool) $ticket->involves_personal_data);
        $this->assertNull($ticket->compliance_routed_at);
        $this->assertNull($ticket->privacy_request_id);
        $this->assertSame(0, PrivacyRequest::query()->where('support_ticket_id', $ticket->id)->count());
    }
}
