<?php

namespace Tests\Feature\Support;

use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private Customer $customer;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'support-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Support Tenant Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::factory()->individual()->forCompany($this->company)->create([
            'email' => 'ticket.customer@example.com',
        ]);

        $this->application = Application::factory()->active()->forCompany($this->company)->create([
            'name' => 'Support Demo App',
        ]);
    }

    public function test_guest_cannot_list_support_tickets(): void
    {
        $this->getJson('/api/v1/support/tickets')->assertUnauthorized();
    }

    public function test_admin_can_create_list_and_view_support_ticket(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'customer_id' => $this->customer->uuid,
            'application_id' => $this->application->uuid,
            'subject' => 'Login failure on mobile',
            'description' => 'Users cannot sign in after the latest release.',
            'priority' => 'high',
            'category' => 'bug_report',
            'source' => 'portal',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ticket.subject', 'Login failure on mobile')
            ->assertJsonPath('data.ticket.category', 'bug_report')
            ->assertJsonPath('data.ticket.status', 'open')
            ->assertJsonPath('data.ticket.priority', 'high');

        $uuid = $create->json('data.ticket.uuid');
        $ticketNumber = $create->json('data.ticket.ticket_number');

        $this->assertNotEmpty($ticketNumber);
        $this->assertStringStartsWith('SUP-', $ticketNumber);

        $this->getJson('/api/v1/support/tickets?search=Login')
            ->assertOk()
            ->assertJsonPath('data.tickets.meta.total', 1)
            ->assertJsonPath('data.statistics.total', 1)
            ->assertJsonPath('data.statistics.open', 1);

        $this->getJson('/api/v1/support/tickets/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.ticket.ticket_number', $ticketNumber)
            ->assertJsonPath('data.ticket.customer.uuid', $this->customer->uuid)
            ->assertJsonPath('data.ticket.application.uuid', $this->application->uuid)
            ->assertJsonPath('data.ticket.company.uuid', $this->company->uuid);
    }

    public function test_support_ticket_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'subject' => '',
            'description' => '',
            'category' => 'invalid-category',
            'priority' => 'invalid',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['subject', 'description', 'category', 'priority']]);
    }

    public function test_admin_can_update_assign_close_archive_and_restore_ticket(): void
    {
        Sanctum::actingAs($this->admin);

        $agent = User::factory()->create(['email' => 'support-agent@example.com']);
        $agent->assignRole('support-agent');

        $ticket = SupportTicket::factory()->forCompany($this->company)->forCustomer($this->customer)->create([
            'subject' => 'Billing discrepancy',
            'category' => 'billing_support',
            'priority' => 'medium',
        ]);

        $this->putJson('/api/v1/support/tickets/'.$ticket->uuid, [
            'subject' => 'Billing discrepancy updated',
            'priority' => 'critical',
            'status' => 'in_progress',
        ])
            ->assertOk()
            ->assertJsonPath('data.ticket.subject', 'Billing discrepancy updated')
            ->assertJsonPath('data.ticket.priority', 'critical')
            ->assertJsonPath('data.ticket.status', 'in_progress');

        $this->postJson('/api/v1/support/tickets/'.$ticket->uuid.'/assign', [
            'type' => 'agent',
            'assigned_to' => $agent->uuid,
        ])
            ->assertOk()
            ->assertJsonPath('data.ticket.assignee.uuid', $agent->uuid)
            ->assertJsonPath('data.ticket.status', 'in_progress')
            ->assertJsonPath('data.ticket.assignment_type', 'agent');

        $this->postJson('/api/v1/support/tickets/'.$ticket->uuid.'/close')
            ->assertOk()
            ->assertJsonPath('data.ticket.status', 'closed');

        $this->assertNotNull($ticket->fresh()->closed_at);

        $this->deleteJson('/api/v1/support/tickets/'.$ticket->uuid)
            ->assertOk()
            ->assertJsonPath('message', 'Support ticket archived successfully.');

        $this->assertSoftDeleted('support_tickets', ['id' => $ticket->id]);

        $this->postJson('/api/v1/support/tickets/'.$ticket->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.ticket.uuid', $ticket->uuid);
    }

    public function test_emergency_category_raises_low_priority_to_emergency(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'subject' => 'Production outage',
            'description' => 'All apps are down.',
            'category' => 'emergency_support',
            'priority' => 'low',
        ])
            ->assertCreated()
            ->assertJsonPath('data.ticket.category', 'emergency_support')
            ->assertJsonPath('data.ticket.priority', 'emergency');
    }

    public function test_tickets_can_be_filtered_by_status_and_category(): void
    {
        Sanctum::actingAs($this->admin);

        SupportTicket::factory()->forCompany($this->company)->create([
            'status' => 'open',
            'category' => 'technical_support',
            'subject' => 'Tech open',
        ]);
        SupportTicket::factory()->forCompany($this->company)->closed()->create([
            'category' => 'billing_support',
            'subject' => 'Billing closed',
        ]);

        $this->getJson('/api/v1/support/tickets?status=open&category=technical_support')
            ->assertOk()
            ->assertJsonPath('data.tickets.meta.total', 1)
            ->assertJsonPath('data.tickets.items.0.subject', 'Tech open');
    }

    public function test_dashboard_returns_statistics_and_lists(): void
    {
        Sanctum::actingAs($this->admin);

        SupportTicket::factory()->forCompany($this->company)->create(['priority' => 'critical', 'status' => 'open']);
        SupportTicket::factory()->forCompany($this->company)->create(['priority' => 'low', 'status' => 'open']);

        $this->getJson('/api/v1/support/dashboard?company='.$this->company->uuid)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statistics.total', 2)
            ->assertJsonStructure([
                'data' => [
                    'statistics',
                    'recent_open' => ['items', 'meta'],
                    'urgent' => ['items', 'meta'],
                ],
            ]);
    }

    public function test_customer_must_belong_to_company(): void
    {
        Sanctum::actingAs($this->admin);

        $otherCompany = Company::query()->create([
            'company_name' => 'Other Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $otherCustomer = Customer::factory()->individual()->forCompany($otherCompany)->create();

        $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'customer_id' => $otherCustomer->uuid,
            'subject' => 'Cross-tenant ticket',
            'description' => 'Should fail',
            'category' => 'general_inquiry',
        ])
            ->assertStatus(422);
    }
}
