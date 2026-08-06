<?php

namespace Tests\Feature\Customers;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerCommunication;
use App\Domains\Customers\Models\CustomerNote;
use App\Domains\Customers\Models\CustomerTask;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerCommunicationCenterTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'comms-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $company = Company::query()->create([
            'company_name' => 'Comms Tenant',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::factory()->business()->forCompany($company)->create([
            'email' => 'comms-buyer@example.com',
            'company_name' => 'Comms Buyer',
        ]);
    }

    public function test_guest_cannot_access_communication_center(): void
    {
        $this->getJson('/api/v1/customer-communication-center/overview?customer='.$this->customer->uuid)
            ->assertUnauthorized();
    }

    public function test_admin_can_manage_notes_tasks_and_communications(): void
    {
        Sanctum::actingAs($this->admin);

        $note = $this->postJson('/api/v1/customer-notes', [
            'customer_id' => $this->customer->uuid,
            'note_type' => 'internal',
            'title' => 'Internal follow-up',
            'body' => 'Discuss renewal options with account owner.',
            'is_pinned' => true,
        ])->assertCreated()
            ->assertJsonPath('data.note.note_type', 'internal')
            ->assertJsonPath('data.note.is_pinned', true);

        $task = $this->postJson('/api/v1/customer-tasks', [
            'customer_id' => $this->customer->uuid,
            'title' => 'Send proposal',
            'priority' => 'high',
            'due_at' => now()->addDays(3)->toISOString(),
            'remind_at' => now()->addDay()->toISOString(),
            'assigned_to' => $this->admin->uuid,
        ])->assertCreated()
            ->assertJsonPath('data.task.title', 'Send proposal')
            ->assertJsonPath('data.task.assignee.uuid', $this->admin->uuid);

        $email = $this->postJson('/api/v1/customer-communications', [
            'customer_id' => $this->customer->uuid,
            'type' => 'email',
            'direction' => 'outbound',
            'subject' => 'Proposal attached',
            'body' => 'Please find the proposal attached.',
            'occurred_at' => now()->toISOString(),
        ])->assertCreated()
            ->assertJsonPath('data.communication.type', 'email');

        $this->postJson('/api/v1/customer-communications', [
            'customer_id' => $this->customer->uuid,
            'type' => 'call',
            'direction' => 'inbound',
            'subject' => 'Discovery call',
            'duration_seconds' => 900,
            'occurred_at' => now()->subHour()->toISOString(),
        ])->assertCreated();

        $this->getJson('/api/v1/customer-notes?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.notes.meta.total', 1);

        $this->getJson('/api/v1/customer-tasks?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.tasks.meta.total', 1);

        $this->getJson('/api/v1/customer-communications?customer='.$this->customer->uuid.'&type=email')
            ->assertOk()
            ->assertJsonPath('data.communications.meta.total', 1)
            ->assertJsonPath('data.statistics.email', 1);

        $this->postJson('/api/v1/customer-tasks/'.$task->json('data.task.uuid').'/complete')
            ->assertOk()
            ->assertJsonPath('data.task.status', 'completed');

        $this->assertNotNull(CustomerTask::query()->where('uuid', $task->json('data.task.uuid'))->value('completed_at'));
        $this->assertDatabaseHas('customer_notes', ['uuid' => $note->json('data.note.uuid')]);
        $this->assertDatabaseHas('customer_communications', ['uuid' => $email->json('data.communication.uuid')]);
    }

    public function test_communication_center_overview_timeline_and_calendar(): void
    {
        Sanctum::actingAs($this->admin);

        CustomerNote::factory()->forCustomer($this->customer)->create([
            'note_type' => 'meeting',
            'title' => 'Kickoff notes',
            'occurred_at' => now()->subDay(),
        ]);

        CustomerTask::factory()->forCustomer($this->customer)->create([
            'title' => 'Reminder task',
            'remind_at' => now()->addDays(2),
            'due_at' => now()->addDays(5),
            'status' => 'open',
        ]);

        CustomerCommunication::factory()->forCustomer($this->customer)->create([
            'type' => 'email',
            'subject' => 'Welcome email',
            'occurred_at' => now()->subHours(2),
        ]);

        $this->getJson('/api/v1/customer-communication-center/overview?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'statistics' => ['notes', 'tasks', 'communications'],
                    'timeline',
                    'activity',
                    'reminders',
                ],
            ]);

        $this->getJson('/api/v1/customer-communication-center/timeline?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/customer-communication-center/calendar?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/customer-tasks/calendar?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonStructure(['data' => ['reminders']]);
    }

    public function test_meeting_note_type_is_supported(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/customer-notes', [
            'customer_id' => $this->customer->uuid,
            'note_type' => 'meeting',
            'title' => 'QBR summary',
            'body' => 'Discussed roadmap and support SLAs.',
            'occurred_at' => now()->toISOString(),
        ])->assertCreated()
            ->assertJsonPath('data.note.note_type', 'meeting');
    }
}
