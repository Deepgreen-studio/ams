<?php

namespace Tests\Feature\Support;

use App\Domains\Companies\Models\Company;
use App\Domains\Support\Enums\SupportSlaEscalationLevel;
use App\Domains\Support\Enums\SupportSlaEscalationTrigger;
use App\Domains\Support\Enums\SupportSlaStatus;
use App\Domains\Support\Models\SupportSlaPolicy;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Services\SupportSlaTrackingService;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SupportSlaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportSlaManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SupportSlaSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'sla-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'SLA Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_ticket_creation_applies_matching_sla_policy(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'subject' => 'Critical outage',
            'description' => 'Production is down',
            'priority' => 'critical',
            'category' => 'bug_report',
            'source' => 'portal',
        ])->assertCreated();

        $ticketUuid = $create->json('data.ticket.uuid');
        $ticket = SupportTicket::query()->where('uuid', $ticketUuid)->firstOrFail();

        $this->assertNotNull($ticket->support_sla_policy_id);
        $this->assertSame(SupportSlaStatus::OnTrack, $ticket->sla_status);
        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertNotNull($ticket->resolution_due_at);

        $policy = SupportSlaPolicy::query()->where('code', 'global-critical')->firstOrFail();
        $this->assertSame($policy->id, $ticket->support_sla_policy_id);
    }

    public function test_company_policy_overrides_global_policy(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/support/sla/policies', [
            'company_id' => $this->company->uuid,
            'name' => 'Company Critical Override',
            'code' => 'company-critical',
            'priority' => 'critical',
            'response_target_minutes' => 10,
            'resolution_target_minutes' => 60,
            'business_hours_only' => false,
            'is_default' => false,
            'escalation_rules' => [
                [
                    'level' => SupportSlaEscalationLevel::Level1->value,
                    'trigger' => SupportSlaEscalationTrigger::ResponseBreached->value,
                    'notify_role' => 'support-agent',
                ],
            ],
        ])->assertCreated();

        $create = $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'subject' => 'Company critical',
            'description' => 'Override test',
            'priority' => 'critical',
            'category' => 'bug_report',
        ])->assertCreated();

        $ticket = SupportTicket::query()->where('uuid', $create->json('data.ticket.uuid'))->firstOrFail();
        $policy = SupportSlaPolicy::query()->where('code', 'company-critical')->firstOrFail();
        $this->assertSame($policy->id, $ticket->support_sla_policy_id);
    }

    public function test_response_breach_creates_escalation(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'subject' => 'Breach me',
            'description' => 'Need escalation',
            'priority' => 'critical',
            'category' => 'bug_report',
        ])->assertCreated();

        $ticket = SupportTicket::query()->where('uuid', $create->json('data.ticket.uuid'))->firstOrFail();
        $ticket->forceFill([
            'first_response_due_at' => now()->subMinutes(5),
            'resolution_due_at' => now()->addHour(),
        ])->save();

        app(SupportSlaTrackingService::class)->evaluateTicket($ticket->fresh());

        $ticket->refresh();
        $this->assertSame(SupportSlaStatus::Breached, $ticket->sla_status);
        $this->assertNotNull($ticket->response_breached_at);

        $this->getJson('/api/v1/support/sla/escalations')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('support_sla_escalations', [
            'support_ticket_id' => $ticket->id,
            'trigger' => SupportSlaEscalationTrigger::ResponseBreached->value,
        ]);
    }

    public function test_sla_dashboard_and_violation_report(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'subject' => 'Tracked ticket',
            'description' => 'Dashboard check',
            'priority' => 'high',
            'category' => 'bug_report',
        ])->assertCreated();

        $this->getJson('/api/v1/support/sla/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'statistics' => [
                        'tracked_tickets',
                        'on_track',
                        'at_risk',
                        'breached',
                        'escalations',
                    ],
                    'timers',
                ],
            ]);

        $this->getJson('/api/v1/support/sla/violations')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_waiting_for_customer_pauses_sla(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'subject' => 'Pause SLA',
            'description' => 'Waiting customer',
            'priority' => 'high',
            'category' => 'general_inquiry',
        ])->assertCreated();

        $uuid = $create->json('data.ticket.uuid');

        $this->postJson('/api/v1/support/tickets/'.$uuid.'/transition', [
            'status' => 'waiting_for_customer',
        ])->assertOk();

        $ticket = SupportTicket::query()->where('uuid', $uuid)->firstOrFail();
        $this->assertSame(SupportSlaStatus::Paused, $ticket->sla_status);
        $this->assertNotNull($ticket->sla_paused_at);
    }

    public function test_holiday_and_calendar_crud(): void
    {
        Sanctum::actingAs($this->admin);

        $calendar = $this->postJson('/api/v1/support/sla/calendars', [
            'company_id' => $this->company->uuid,
            'name' => 'Company Hours',
            'timezone' => 'UTC',
            'business_hours' => [
                'monday' => ['08:00', '16:00'],
                'tuesday' => ['08:00', '16:00'],
                'wednesday' => ['08:00', '16:00'],
                'thursday' => ['08:00', '16:00'],
                'friday' => ['08:00', '16:00'],
            ],
            'is_default' => true,
        ])->assertCreated();

        $calendarUuid = $calendar->json('data.calendar.uuid');

        $this->postJson('/api/v1/support/sla/holidays', [
            'company_id' => $this->company->uuid,
            'calendar_id' => $calendarUuid,
            'name' => 'Company Day Off',
            'holiday_date' => Carbon::now()->addMonth()->toDateString(),
            'is_recurring' => false,
        ])->assertCreated();

        $this->getJson('/api/v1/support/sla/holidays?company_id='.$this->company->uuid)
            ->assertOk()
            ->assertJsonPath('data.holidays.meta.total', 1);
    }
}
