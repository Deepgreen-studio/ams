<?php

namespace Tests\Feature\Notifications;

use App\Domains\Notifications\Enums\NotificationChannel as NotificationChannelEnum;
use App\Domains\Notifications\Enums\NotificationEventKey;
use App\Domains\Notifications\Enums\NotificationTemplateStatus;
use App\Domains\Notifications\Models\NotificationTemplate;
use App\Domains\Notifications\Models\NotificationTemplateApproval;
use App\Domains\Notifications\Models\NotificationTemplateVersion;
use App\Domains\Notifications\Notifications\TemplatedNotification;
use App\Models\User;
use Database\Seeders\NotificationChannelSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(NotificationChannelSeeder::class);
        $this->seed(NotificationTemplateSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->manager = User::factory()->create(['email' => 'template-manager@example.com']);
        $this->manager->assignRole('support-manager');
        $this->manager->givePermissionTo([
            'notifications.view',
            'notifications.create',
            'notifications.update',
            'notifications.delete',
            'notifications.approve',
            'notifications.publish',
        ]);
    }

    public function test_can_create_localized_email_template_with_version(): void
    {
        Sanctum::actingAs($this->manager);

        $response = $this->postJson('/api/v1/notifications/templates', [
            'event_key' => NotificationEventKey::TicketCreated->value,
            'channel' => NotificationChannelEnum::Email->value,
            'locale' => 'es',
            'name' => 'Ticket Created Email ES',
            'subject' => 'Nuevo ticket {{ticket_number}}',
            'body' => '<p>Hola {{recipient_name}}</p>',
            'priority' => 'high',
            'change_summary' => 'Spanish localization',
        ])->assertCreated()
            ->assertJsonPath('data.template.locale', 'es')
            ->assertJsonPath('data.template.workflow_status', 'draft')
            ->assertJsonPath('data.template.current_version', 1);

        $uuid = $response->json('data.template.uuid');
        $this->assertDatabaseHas('notification_template_versions', [
            'version' => 1,
            'locale' => 'es',
        ]);

        $this->getJson('/api/v1/notifications/templates/'.$uuid.'/versions')
            ->assertOk()
            ->assertJsonPath('data.versions.0.version', 1);
    }

    public function test_preview_and_test_send(): void
    {
        Sanctum::actingAs($this->manager);

        $template = NotificationTemplate::query()
            ->where('channel', NotificationChannelEnum::Email->value)
            ->where('is_system', true)
            ->firstOrFail();

        $this->postJson('/api/v1/notifications/templates/'.$template->uuid.'/preview', [
            'variables' => [
                'recipient_name' => 'Sam',
                'ticket_number' => 'T-77',
                'subject' => 'Hello',
            ],
        ])->assertOk()
            ->assertJsonPath('data.preview.channel', 'email');

        $this->postJson('/api/v1/notifications/templates/'.$template->uuid.'/test-send', [
            'email' => 'test@example.com',
            'variables' => [
                'recipient_name' => 'Sam',
                'ticket_number' => 'T-77',
                'subject' => 'Hello',
            ],
        ])->assertOk()
            ->assertJsonPath('data.sent', true);

        Notification::assertSentOnDemand(TemplatedNotification::class);
    }

    public function test_approval_workflow_and_publish(): void
    {
        Sanctum::actingAs($this->manager);

        $create = $this->postJson('/api/v1/notifications/templates', [
            'event_key' => NotificationEventKey::TicketAssigned->value,
            'channel' => NotificationChannelEnum::Sms->value,
            'locale' => 'en',
            'name' => 'Assignment SMS Draft',
            'body' => 'Assigned {{ticket_number}}',
        ])->assertCreated();

        $uuid = $create->json('data.template.uuid');

        $this->postJson('/api/v1/notifications/templates/'.$uuid.'/submit', [
            'comments' => 'Please review SMS copy',
        ])->assertOk()
            ->assertJsonPath('data.template.workflow_status', 'review');

        $approval = NotificationTemplateApproval::query()->latest('id')->firstOrFail();

        $this->postJson('/api/v1/notifications/templates/approvals/'.$approval->uuid.'/approve', [
            'comments' => 'Looks good',
        ])->assertOk()
            ->assertJsonPath('data.template.workflow_status', 'approved');

        $this->postJson('/api/v1/notifications/templates/'.$uuid.'/publish')
            ->assertOk()
            ->assertJsonPath('data.template.workflow_status', 'published');

        $this->assertSame(
            NotificationTemplateStatus::Published,
            NotificationTemplate::query()->where('uuid', $uuid)->firstOrFail()->workflow_status
        );
    }

    public function test_version_compare_and_restore(): void
    {
        Sanctum::actingAs($this->manager);

        $create = $this->postJson('/api/v1/notifications/templates', [
            'event_key' => NotificationEventKey::ReplyAdded->value,
            'channel' => NotificationChannelEnum::Push->value,
            'name' => 'Reply Push',
            'body' => 'Original body {{ticket_number}}',
        ])->assertCreated();

        $uuid = $create->json('data.template.uuid');

        $this->putJson('/api/v1/notifications/templates/'.$uuid, [
            'body' => 'Updated body {{ticket_number}}',
            'change_summary' => 'Copy update',
        ])->assertOk()
            ->assertJsonPath('data.template.current_version', 2);

        $versions = NotificationTemplateVersion::query()
            ->whereHas('template', fn ($q) => $q->where('uuid', $uuid))
            ->orderBy('version')
            ->get();

        $this->assertCount(2, $versions);

        $this->getJson('/api/v1/notifications/templates/'.$uuid.'/versions/compare?from='.$versions[0]->uuid.'&to='.$versions[1]->uuid)
            ->assertOk()
            ->assertJsonStructure(['data' => ['comparison' => ['changes', 'changed_fields']]]);

        $this->postJson('/api/v1/notifications/templates/'.$uuid.'/versions/'.$versions[0]->uuid.'/restore', [
            'reason' => 'Rollback copy',
        ])->assertOk()
            ->assertJsonPath('data.template.current_version', 3)
            ->assertJsonPath('data.template.workflow_status', 'draft');
    }
}
