<?php

namespace Tests\Feature\Support;

use App\Domains\Companies\Models\Company;
use App\Domains\Support\Models\SupportTicket;
use App\Domains\Support\Models\SupportTicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketConversationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private SupportTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
        config(['filesystems.support_attachments_disk' => 'local']);

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'conversation-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Conversation Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->ticket = SupportTicket::factory()->forCompany($this->company)->create([
            'subject' => 'Need help with login',
            'status' => 'open',
        ]);
    }

    public function test_agent_can_post_public_private_and_internal_messages(): void
    {
        Sanctum::actingAs($this->admin);

        foreach (['public', 'private', 'internal'] as $visibility) {
            $this->postJson('/api/v1/support/tickets/'.$this->ticket->uuid.'/messages', [
                'body' => '<p>Reply for '.$visibility.'</p>',
                'visibility' => $visibility,
                'body_format' => 'html',
            ])
                ->assertCreated()
                ->assertJsonPath('data.message.visibility', $visibility)
                ->assertJsonPath('success', true);
        }

        $this->getJson('/api/v1/support/tickets/'.$this->ticket->uuid.'/messages')
            ->assertOk()
            ->assertJsonPath('data.messages.0.body', '<p>Reply for public</p>')
            ->assertJsonCount(3, 'data.messages');
    }

    public function test_message_with_attachment_upload_download_and_preview(): void
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->image('screenshot.png', 200, 120);

        $create = $this->post('/api/v1/support/tickets/'.$this->ticket->uuid.'/messages', [
            'body' => '<p>See attached screenshot</p>',
            'visibility' => 'public',
            'attachments' => [$file],
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $attachmentUuid = $create->json('data.message.attachments.0.uuid');
        $this->assertNotEmpty($attachmentUuid);
        $this->assertSame('screenshot', $create->json('data.message.attachments.0.attachment_type'));

        $this->get('/api/v1/support/tickets/'.$this->ticket->uuid.'/attachments/'.$attachmentUuid.'/download')
            ->assertOk();

        $this->get('/api/v1/support/tickets/'.$this->ticket->uuid.'/attachments/'.$attachmentUuid.'/preview')
            ->assertOk();
    }

    public function test_document_and_video_attachment_types_are_detected(): void
    {
        Sanctum::actingAs($this->admin);

        $pdf = UploadedFile::fake()->create('report.pdf', 120, 'application/pdf');
        $video = UploadedFile::fake()->create('clip.mp4', 240, 'video/mp4');

        $this->post('/api/v1/support/tickets/'.$this->ticket->uuid.'/attachments', [
            'attachments' => [$pdf, $video],
        ], [
            'Accept' => 'application/json',
        ])
            ->assertCreated()
            ->assertJsonPath('data.attachments.0.attachment_type', 'document')
            ->assertJsonPath('data.attachments.1.attachment_type', 'video');
    }

    public function test_read_status_is_tracked_per_user(): void
    {
        Sanctum::actingAs($this->admin);

        $agent = User::factory()->create(['email' => 'reader-agent@example.com']);
        $agent->assignRole('support-agent');

        $message = SupportTicketMessage::query()->create([
            'support_ticket_id' => $this->ticket->id,
            'company_id' => $this->company->id,
            'author_id' => $this->admin->id,
            'author_type' => 'agent',
            'visibility' => 'public',
            'body' => '<p>Hello agent</p>',
            'body_format' => 'html',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        Sanctum::actingAs($agent);

        $this->getJson('/api/v1/support/tickets/'.$this->ticket->uuid.'/messages')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonPath('data.messages.0.is_read', false);

        $this->postJson('/api/v1/support/tickets/'.$this->ticket->uuid.'/messages/read', [
            'message_ids' => [$message->uuid],
        ])
            ->assertOk()
            ->assertJsonPath('data.marked', 1);

        $this->getJson('/api/v1/support/tickets/'.$this->ticket->uuid.'/messages')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0)
            ->assertJsonPath('data.messages.0.is_read', true);
    }

    public function test_rejects_disallowed_attachment_extension(): void
    {
        Sanctum::actingAs($this->admin);

        $exe = UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream');

        $this->post('/api/v1/support/tickets/'.$this->ticket->uuid.'/attachments', [
            'attachments' => [$exe],
        ], [
            'Accept' => 'application/json',
        ])->assertStatus(422);
    }

    public function test_guest_cannot_access_conversation(): void
    {
        $this->getJson('/api/v1/support/tickets/'.$this->ticket->uuid.'/messages')
            ->assertUnauthorized();
    }
}
