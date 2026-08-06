<?php

namespace Tests\Feature\Support;

use App\Domains\Companies\Models\Company;
use App\Domains\Companies\Models\Department;
use App\Domains\Companies\Models\Team;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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

        $this->admin = User::factory()->create(['email' => 'workflow-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Workflow Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_kanban_board_groups_tickets_by_status(): void
    {
        Sanctum::actingAs($this->admin);

        SupportTicket::factory()->forCompany($this->company)->create(['status' => 'open', 'subject' => 'Open one']);
        SupportTicket::factory()->forCompany($this->company)->create(['status' => 'pending', 'subject' => 'Pending one']);
        SupportTicket::factory()->forCompany($this->company)->create(['status' => 'in_progress', 'subject' => 'Active one']);

        $response = $this->getJson('/api/v1/support/tickets/board?company='.$this->company->uuid)
            ->assertOk()
            ->assertJsonPath('success', true);

        $columns = collect($response->json('data.columns'));
        $this->assertTrue($columns->contains(fn ($column) => $column['status'] === 'open' && $column['count'] >= 1));
        $this->assertTrue($columns->contains(fn ($column) => $column['status'] === 'waiting_for_customer'));
        $this->assertTrue($columns->contains(fn ($column) => $column['status'] === 'reopened'));
    }

    public function test_queue_returns_unassigned_and_critical_tickets(): void
    {
        Sanctum::actingAs($this->admin);

        SupportTicket::factory()->forCompany($this->company)->create([
            'status' => 'open',
            'priority' => 'low',
            'assigned_to' => null,
        ]);
        SupportTicket::factory()->forCompany($this->company)->create([
            'status' => 'open',
            'priority' => 'emergency',
            'assigned_to' => null,
        ]);

        $this->getJson('/api/v1/support/tickets/queue?queue=unassigned&company='.$this->company->uuid)
            ->assertOk()
            ->assertJsonPath('data.tickets.meta.total', 2);

        $this->getJson('/api/v1/support/tickets/queue?queue=critical&company='.$this->company->uuid)
            ->assertOk()
            ->assertJsonPath('data.tickets.meta.total', 1)
            ->assertJsonPath('data.tickets.items.0.priority', 'emergency');
    }

    public function test_status_transition_and_timeline_are_recorded(): void
    {
        Sanctum::actingAs($this->admin);

        $ticket = SupportTicket::factory()->forCompany($this->company)->create(['status' => 'open']);

        $this->postJson('/api/v1/support/tickets/'.$ticket->uuid.'/transition', [
            'status' => 'waiting_for_customer',
            'comments' => 'Asked customer for logs',
        ])
            ->assertOk()
            ->assertJsonPath('data.ticket.status', 'waiting_for_customer');

        $this->getJson('/api/v1/support/tickets/'.$ticket->uuid.'/timeline')
            ->assertOk()
            ->assertJsonPath('data.timeline.0.to_status', 'waiting_for_customer')
            ->assertJsonPath('data.timeline.0.comments', 'Asked customer for logs');
    }

    public function test_invalid_transition_is_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        $ticket = SupportTicket::factory()->forCompany($this->company)->closed()->create();

        $this->postJson('/api/v1/support/tickets/'.$ticket->uuid.'/transition', [
            'status' => 'in_progress',
        ])->assertStatus(422);
    }

    public function test_reopen_closed_ticket(): void
    {
        Sanctum::actingAs($this->admin);

        $ticket = SupportTicket::factory()->forCompany($this->company)->closed()->create();

        $this->postJson('/api/v1/support/tickets/'.$ticket->uuid.'/reopen', [
            'comments' => 'Customer reported recurrence',
        ])
            ->assertOk()
            ->assertJsonPath('data.ticket.status', 'reopened');

        $this->assertNull($ticket->fresh()->closed_at);
    }

    public function test_auto_assignment_round_robins_support_agents(): void
    {
        Sanctum::actingAs($this->admin);

        $agentA = User::factory()->create(['email' => 'agent-a@example.com']);
        $agentA->assignRole('support-agent');
        $agentB = User::factory()->create(['email' => 'agent-b@example.com']);
        $agentB->assignRole('support-agent');

        $ticketOne = SupportTicket::factory()->forCompany($this->company)->create(['status' => 'open']);
        $ticketTwo = SupportTicket::factory()->forCompany($this->company)->create(['status' => 'open']);

        $first = $this->postJson('/api/v1/support/tickets/'.$ticketOne->uuid.'/assign', [
            'type' => 'auto',
        ])->assertOk();

        $second = $this->postJson('/api/v1/support/tickets/'.$ticketTwo->uuid.'/assign', [
            'type' => 'auto',
        ])->assertOk();

        $firstAssignee = $first->json('data.ticket.assignee.uuid');
        $secondAssignee = $second->json('data.ticket.assignee.uuid');

        $this->assertNotNull($firstAssignee);
        $this->assertNotNull($secondAssignee);
        $this->assertNotSame($firstAssignee, $secondAssignee);
        $this->assertEquals('auto', $first->json('data.ticket.assignment_type'));
    }

    public function test_department_and_team_assignment(): void
    {
        Sanctum::actingAs($this->admin);

        $manager = User::factory()->create(['email' => 'team-manager@example.com']);
        $manager->assignRole('support-manager');

        $department = Department::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Customer Care',
            'status' => 'active',
        ]);

        $team = Team::query()->create([
            'company_id' => $this->company->id,
            'department_id' => $department->id,
            'name' => 'Tier 1',
            'manager_id' => $manager->id,
            'status' => 'active',
        ]);

        $ticket = SupportTicket::factory()->forCompany($this->company)->create(['status' => 'open']);

        $this->postJson('/api/v1/support/tickets/'.$ticket->uuid.'/assign', [
            'type' => 'department',
            'department_id' => $department->uuid,
        ])
            ->assertOk()
            ->assertJsonPath('data.ticket.assignment_type', 'department')
            ->assertJsonPath('data.ticket.department.uuid', $department->uuid)
            ->assertJsonPath('data.ticket.assignee', null);

        $this->postJson('/api/v1/support/tickets/'.$ticket->uuid.'/assign', [
            'type' => 'team',
            'team_id' => $team->uuid,
        ])
            ->assertOk()
            ->assertJsonPath('data.ticket.assignment_type', 'team')
            ->assertJsonPath('data.ticket.team.uuid', $team->uuid)
            ->assertJsonPath('data.ticket.assignee.uuid', $manager->uuid)
            ->assertJsonPath('data.ticket.status', 'in_progress');
    }
}
