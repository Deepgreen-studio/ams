<?php

namespace Tests\Feature\Notifications;

use App\Domains\Companies\Models\Company;
use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationStatus;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\NotificationChannel;
use App\Domains\Notifications\Models\NotificationLog;
use App\Domains\Notifications\Models\NotificationTemplate;
use App\Domains\Notifications\Notifications\TemplatedNotification;
use App\Models\User;
use Database\Seeders\NotificationChannelSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $agent;

    private User $manager;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        NotificationFacade::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(NotificationChannelSeeder::class);
        $this->seed(NotificationTemplateSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'notify-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->agent = User::factory()->create(['email' => 'notify-agent@example.com']);
        $this->agent->assignRole('support-agent');
        $this->agent->givePermissionTo(['support.view', 'support.create', 'support.update', 'notifications.view']);

        $this->manager = User::factory()->create(['email' => 'notify-manager@example.com']);
        $this->manager->assignRole('support-manager');
        $this->manager->givePermissionTo([
            'support.view', 'support.create', 'support.update', 'support.manage',
            'notifications.view', 'notifications.create', 'notifications.update', 'notifications.delete',
            'notifications.approve', 'notifications.publish',
        ]);

        $this->company = Company::query()->create([
            'company_name' => 'Notify Tenant Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_ticket_assignment_writes_enterprise_notifications_and_logs(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/support/tickets', [
            'company_id' => $this->company->uuid,
            'subject' => 'Notify me',
            'description' => 'Assignment should notify agent and managers',
            'category' => 'bug_report',
            'priority' => 'high',
            'assigned_to' => $this->agent->uuid,
        ])->assertCreated();

        $this->assertTrue(
            Notification::query()
                ->where('user_id', $this->agent->id)
                ->where('event_key', NotificationEventKey::TicketCreated->value)
                ->exists()
        );

        $this->assertTrue(
            NotificationLog::query()
                ->where('event_key', NotificationEventKey::TicketCreated->value)
                ->where('notifiable_id', $this->agent->id)
                ->exists()
        );

        NotificationFacade::assertSentTo($this->agent, TemplatedNotification::class);
    }

    public function test_preferences_can_be_updated(): void
    {
        Sanctum::actingAs($this->agent);

        $this->putJson('/api/v1/notifications/preferences', [
            'preferences' => collect(NotificationEventKey::cases())->map(fn ($event) => [
                'event_key' => $event->value,
                'email_enabled' => false,
                'in_app_enabled' => true,
                'sms_enabled' => false,
                'push_enabled' => false,
                'whatsapp_enabled' => false,
                'slack_enabled' => false,
                'teams_enabled' => false,
                'webhook_enabled' => false,
            ])->all(),
        ])->assertOk();

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->agent->id,
            'event_key' => NotificationEventKey::TicketAssigned->value,
            'email_enabled' => 0,
            'in_app_enabled' => 1,
        ]);
    }

    public function test_in_app_history_mark_read_and_read_all(): void
    {
        Sanctum::actingAs($this->agent);

        $notification = Notification::factory()->create([
            'user_id' => $this->agent->id,
            'channel' => NotificationChannelEnum::InApp->value,
            'status' => NotificationStatus::Sent->value,
            'title' => 'Ticket assigned',
            'message' => 'You were assigned T-100',
            'event_key' => NotificationEventKey::TicketAssigned->value,
            'read_at' => null,
        ]);

        $this->getJson('/api/v1/notifications?unread=1')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonPath('data.notifications.meta.total', 1);

        $this->postJson('/api/v1/notifications/'.$notification->uuid.'/read')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        Notification::factory()->create([
            'user_id' => $this->agent->id,
            'channel' => NotificationChannelEnum::InApp->value,
            'status' => NotificationStatus::Sent->value,
            'read_at' => null,
        ]);

        $this->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_dashboard_and_channels_endpoints(): void
    {
        Sanctum::actingAs($this->manager);

        $this->getJson('/api/v1/notifications/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'statistics',
                    'unread_count',
                    'recent',
                    'channels',
                    'delivery_statistics',
                ],
            ]);

        $this->assertGreaterThan(0, NotificationChannel::query()->count());

        $this->getJson('/api/v1/notifications/channels')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_templates_and_delivery_logs_endpoints(): void
    {
        Sanctum::actingAs($this->manager);

        $this->getJson('/api/v1/notifications/templates')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertGreaterThan(0, NotificationTemplate::query()->count());

        NotificationLog::query()->create([
            'event_key' => NotificationEventKey::TicketCreated->value,
            'channel' => 'email',
            'status' => 'sent',
            'notifiable_type' => $this->agent->getMorphClass(),
            'notifiable_id' => $this->agent->id,
            'recipient' => $this->agent->email,
            'subject' => 'Test',
            'body_preview' => 'Preview',
            'queued_at' => now(),
            'sent_at' => now(),
        ]);

        $this->getJson('/api/v1/notifications/delivery-logs')
            ->assertOk()
            ->assertJsonPath('data.statistics.sent', 1);

        $this->getJson('/api/v1/notifications/logs')
            ->assertOk()
            ->assertJsonPath('data.statistics.sent', 1);
    }

    public function test_notification_center_endpoint(): void
    {
        Sanctum::actingAs($this->agent);

        $this->getJson('/api/v1/notifications/center')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'unread_count',
                    'recent',
                    'channels',
                    'events',
                ],
            ]);
    }

    public function test_unread_endpoint(): void
    {
        Sanctum::actingAs($this->agent);

        Notification::factory()->count(2)->create([
            'user_id' => $this->agent->id,
            'channel' => NotificationChannelEnum::InApp->value,
            'read_at' => null,
        ]);

        $this->getJson('/api/v1/notifications/unread')
            ->assertOk()
            ->assertJsonPath('data.notifications.meta.total', 2);
    }
}
