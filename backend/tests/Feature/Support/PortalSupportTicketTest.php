<?php

namespace Tests\Feature\Support;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\CustomerType;
use App\Domains\Customers\Models\Customer;
use App\Domains\Support\Enums\SupportTicketMessageAuthorType;
use App\Domains\Support\Enums\SupportTicketMessageVisibility;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Models\SupportTicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortalSupportTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $portalUser;

    private User $otherPortalUser;

    private User $admin;

    private Customer $customer;

    private Customer $otherCustomer;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->company = Company::query()->create([
            'company_name' => 'Portal Tenant Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::query()->create([
            'company_id' => $this->company->id,
            'customer_type' => CustomerType::Individual->value,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada.portal@example.com',
            'status' => CustomerStatus::Active->value,
            'timezone' => 'UTC',
            'language' => 'en',
        ]);

        $this->otherCustomer = Customer::query()->create([
            'company_id' => $this->company->id,
            'customer_type' => CustomerType::Individual->value,
            'first_name' => 'Other',
            'last_name' => 'Customer',
            'email' => 'other.portal@example.com',
            'status' => CustomerStatus::Active->value,
            'timezone' => 'UTC',
            'language' => 'en',
        ]);

        $this->portalUser = User::factory()->create([
            'email' => 'ada.portal@example.com',
            'customer_id' => $this->customer->id,
        ]);
        $this->portalUser->assignRole('customer');

        $this->otherPortalUser = User::factory()->create([
            'email' => 'other.portal@example.com',
            'customer_id' => $this->otherCustomer->id,
        ]);
        $this->otherPortalUser->assignRole('customer');

        $this->admin = User::factory()->create(['email' => 'portal-admin@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_portal_customer_can_submit_list_and_view_own_tickets(): void
    {
        Sanctum::actingAs($this->portalUser);

        $create = $this->postJson('/api/v1/portal/support/tickets', [
            'subject' => 'Cannot login',
            'description' => 'I get an error on the login form.',
            'category' => 'technical_support',
            'priority' => 'high',
        ])->assertCreated();

        $uuid = $create->json('data.ticket.uuid');
        $this->assertSame('portal', $create->json('data.ticket.source'));
        $this->assertSame($this->customer->uuid, $create->json('data.ticket.customer.uuid'));

        $this->getJson('/api/v1/portal/support/tickets')
            ->assertOk()
            ->assertJsonPath('data.tickets.meta.total', 1);

        $this->getJson('/api/v1/portal/support/tickets/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.ticket.subject', 'Cannot login');
    }

    public function test_portal_customer_cannot_view_other_customer_tickets(): void
    {
        Sanctum::actingAs($this->portalUser);

        $uuid = $this->postJson('/api/v1/portal/support/tickets', [
            'subject' => 'Mine',
            'description' => 'Only for me',
            'category' => 'general_inquiry',
        ])->assertCreated()->json('data.ticket.uuid');

        Sanctum::actingAs($this->otherPortalUser);

        $this->getJson('/api/v1/portal/support/tickets/'.$uuid)
            ->assertForbidden();

        $this->getJson('/api/v1/portal/support/tickets')
            ->assertOk()
            ->assertJsonPath('data.tickets.meta.total', 0);
    }

    public function test_portal_reply_is_customer_public_and_hides_internal_notes(): void
    {
        Sanctum::actingAs($this->portalUser);

        $uuid = $this->postJson('/api/v1/portal/support/tickets', [
            'subject' => 'Billing question',
            'description' => 'Need invoice copy',
            'category' => 'billing_support',
        ])->assertCreated()->json('data.ticket.uuid');

        $ticket = SupportTicket::query()->where('uuid', $uuid)->firstOrFail();

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'company_id' => $ticket->company_id,
            'author_id' => $this->admin->id,
            'author_type' => SupportTicketMessageAuthorType::Agent->value,
            'visibility' => SupportTicketMessageVisibility::Internal->value,
            'body' => '<p>Internal note only</p>',
            'body_format' => 'html',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'company_id' => $ticket->company_id,
            'author_id' => $this->admin->id,
            'author_type' => SupportTicketMessageAuthorType::Agent->value,
            'visibility' => SupportTicketMessageVisibility::Public->value,
            'body' => '<p>Public agent reply</p>',
            'body_format' => 'html',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->postJson('/api/v1/portal/support/tickets/'.$uuid.'/messages', [
            'body' => '<p>Thanks, here is more detail.</p>',
        ])
            ->assertCreated()
            ->assertJsonPath('data.message.author_type', 'customer')
            ->assertJsonPath('data.message.visibility', 'public');

        $messages = $this->getJson('/api/v1/portal/support/tickets/'.$uuid.'/messages')
            ->assertOk()
            ->json('data.messages');

        $this->assertCount(2, $messages);
        $this->assertFalse(collect($messages)->contains(fn ($m) => ($m['visibility'] ?? null) === 'internal'));
    }

    public function test_unlinked_user_cannot_use_portal(): void
    {
        $user = User::factory()->create(['email' => 'no-link@example.com']);
        $user->assignRole('customer');

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/portal/me')->assertForbidden();
    }
}
