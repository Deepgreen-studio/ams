<?php

namespace Tests\Feature\Integrations;

use App\Domains\Applications\Enums\ApplicationCategory;
use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Enums\ApplicationVisibility;
use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Enums\WebhookDirection;
use App\Domains\Integrations\Enums\WebhookStatus;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use App\Shared\Services\Webhook\SignatureValidator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenericSupportIncomingWebhookIngestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private Webhook $webhook;

    private string $secret = 'any-app-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['email' => 'admin@ams.test']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Shop Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        Application::query()->create([
            'company_id' => $this->company->id,
            'slug' => 'shop-web',
            'name' => 'Shop Web',
            'platform' => ApplicationPlatform::Web,
            'category' => ApplicationCategory::Other,
            'status' => ApplicationStatus::Active,
            'visibility' => ApplicationVisibility::Internal,
            'current_version' => '1.0.0',
            'minimum_supported_version' => '1.0.0',
        ]);

        $this->webhook = Webhook::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Shop Incoming',
            'slug' => 'shop-web',
            'direction' => WebhookDirection::Incoming,
            'status' => WebhookStatus::Active,
            'secret' => $this->secret,
            'signature_algorithm' => 'hmac_sha256',
            'signature_header' => 'X-AMS-Signature',
            'subscribed_events' => [
                'support.sms.received',
                'support.message.received',
                'support.ticket.created',
            ],
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_any_app_support_sms_creates_ticket_with_sms_source(): void
    {
        $payload = [
            'event' => 'support.sms.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message_id' => 'sms-1001',
                'from' => '+8801700000000',
                'to' => '+8801800000000',
                'body' => 'I need help with my delivery.',
                'application_slug' => 'shop-web',
                'customer_name' => 'Rahim',
            ],
        ];

        $this->postSigned($payload)
            ->assertOk()
            ->assertJsonPath('data.received', true)
            ->assertJsonPath('data.ingest.handled', true)
            ->assertJsonPath('data.ingest.skipped', false);

        $ticket = SupportTicket::query()
            ->where('company_id', $this->company->id)
            ->where('description', 'like', '%[ams-support-ingest:shop-web:support.sms.received:sms-1001]%')
            ->first();

        $this->assertNotNull($ticket);
        $this->assertSame('sms', $ticket->source->value);
        $this->assertSame('I need help with my delivery.', strtok($ticket->description, "\n"));
        $this->assertNotNull($ticket->application_id);
        $this->assertSame(1, $ticket->messages()->count());
        $this->assertSame('customer', $ticket->messages()->first()->author_type->value);
    }

    public function test_follow_up_sms_appends_to_same_ticket_when_ticket_uuid_provided(): void
    {
        $first = [
            'event' => 'support.sms.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message_id' => 'sms-thread-1',
                'from' => '+8801700000099',
                'to' => '+8801800000000',
                'body' => 'First SMS',
            ],
        ];

        $create = $this->postSigned($first)->assertOk();
        $ticketUuid = $create->json('data.ingest.support_ticket_uuid');
        $this->assertNotEmpty($ticketUuid);

        $followUp = [
            'event' => 'support.sms.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message_id' => 'sms-thread-2',
                'from' => '+8801700000099',
                'to' => '+8801800000000',
                'body' => 'Second SMS on same thread',
                'ticket_uuid' => $ticketUuid,
            ],
        ];

        $this->postSigned($followUp)
            ->assertOk()
            ->assertJsonPath('data.ingest.actions.0', 'support_ticket_message_appended')
            ->assertJsonPath('data.ingest.support_ticket_uuid', $ticketUuid);

        $this->assertSame(1, SupportTicket::query()->where('company_id', $this->company->id)->count());
        $ticket = SupportTicket::query()->where('uuid', $ticketUuid)->first();
        $this->assertNotNull($ticket);
        $this->assertSame(2, $ticket->messages()->count());
    }

    public function test_support_message_received_uses_api_source_by_default(): void
    {
        $payload = [
            'event' => 'support.message.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message_id' => 'msg-55',
                'body' => 'Website contact form: please call me back.',
                'channel' => 'web',
            ],
        ];

        $this->postSigned($payload)
            ->assertOk()
            ->assertJsonPath('data.ingest.handled', true);

        $ticket = SupportTicket::query()->where('company_id', $this->company->id)->first();
        $this->assertNotNull($ticket);
        $this->assertSame('web', $ticket->source->value);
    }

    public function test_duplicate_sms_is_idempotent(): void
    {
        $payload = [
            'event' => 'support.sms.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message_id' => 'sms-dup-1',
                'from' => '+8801700000001',
                'body' => 'Duplicate check',
            ],
        ];

        $this->postSigned($payload)->assertOk()->assertJsonPath('data.ingest.skipped', false);
        $this->postSigned($payload)
            ->assertOk()
            ->assertJsonPath('data.ingest.skipped', true)
            ->assertJsonPath('data.ingest.actions.0', 'idempotent_skip');

        $this->assertSame(1, SupportTicket::query()->where('company_id', $this->company->id)->count());
    }

    public function test_complaint_form_type_creates_support_only(): void
    {
        $payload = [
            'event' => 'support.message.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message_id' => 'form-complaint-1',
                'form_type' => 'complaint',
                'subject' => 'Complaint about delayed delivery',
                'body' => 'My order is 5 days late.',
                'channel' => 'web',
                'involves_personal_data' => false,
            ],
        ];

        $this->postSigned($payload)
            ->assertOk()
            ->assertJsonPath('data.ingest.form_type', 'complaint')
            ->assertJsonPath('data.ingest.destination', 'support')
            ->assertJsonPath('data.ingest.actions.0', 'support_ticket_created');

        $this->assertSame(1, SupportTicket::query()->where('company_id', $this->company->id)->count());
        $this->assertSame(0, \App\Domains\Compliance\Models\PrivacyRequest::query()->where('company_id', $this->company->id)->count());
    }

    public function test_compliance_case_form_type_skips_support(): void
    {
        $payload = [
            'event' => 'support.message.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message_id' => 'form-case-1',
                'form_type' => 'compliance_case',
                'subject' => 'Compliance case from website',
                'body' => 'Please open a compliance case.',
                'channel' => 'web',
            ],
        ];

        $this->postSigned($payload)
            ->assertOk()
            ->assertJsonPath('data.ingest.destination', 'compliance_case')
            ->assertJsonPath('data.ingest.actions.0', 'compliance_case_created');

        $this->assertSame(0, SupportTicket::query()->where('company_id', $this->company->id)->count());
        $this->assertSame(1, \App\Domains\Compliance\Models\ComplianceCase::query()->where('company_id', $this->company->id)->count());
    }

    public function test_breach_form_type_creates_breach_only(): void
    {
        $payload = [
            'event' => 'support.message.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message_id' => 'form-breach-1',
                'form_type' => 'breach',
                'subject' => 'Possible data breach',
                'body' => 'Personal data may have been exposed.',
                'priority' => 'critical',
            ],
        ];

        $this->postSigned($payload)
            ->assertOk()
            ->assertJsonPath('data.ingest.destination', 'breach')
            ->assertJsonPath('data.ingest.actions.0', 'compliance_breach_created');

        $this->assertSame(0, SupportTicket::query()->where('company_id', $this->company->id)->count());
        $this->assertSame(1, \App\Domains\Compliance\Models\DataBreach::query()->where('company_id', $this->company->id)->count());
    }

    public function test_consent_form_type_creates_privacy_request_only(): void
    {
        $payload = [
            'event' => 'support.message.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message_id' => 'form-consent-1',
                'form_type' => 'consent',
                'subject' => 'Withdraw my consent',
                'body' => 'Please withdraw my marketing consent.',
                'customer_email' => 'nadia@example.com',
                'customer_name' => 'Nadia',
            ],
        ];

        $this->postSigned($payload)
            ->assertOk()
            ->assertJsonPath('data.ingest.destination', 'privacy_only')
            ->assertJsonPath('data.ingest.actions.0', 'compliance_privacy_request_created');

        $this->assertSame(0, SupportTicket::query()->where('company_id', $this->company->id)->count());
        $privacy = \App\Domains\Compliance\Models\PrivacyRequest::query()->where('company_id', $this->company->id)->first();
        $this->assertNotNull($privacy);
        $this->assertSame('consent_withdrawal', $privacy->request_type->value);
    }

    public function test_dpia_form_type_creates_dpia_only(): void
    {
        $payload = [
            'event' => 'support.message.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message_id' => 'form-dpia-1',
                'form_type' => 'dpia',
                'subject' => 'DPIA request',
                'body' => 'Please start a DPIA.',
            ],
        ];

        $this->postSigned($payload)
            ->assertOk()
            ->assertJsonPath('data.ingest.destination', 'dpia')
            ->assertJsonPath('data.ingest.actions.0', 'compliance_dpia_created');

        $this->assertSame(0, SupportTicket::query()->where('company_id', $this->company->id)->count());
        $this->assertSame(1, \App\Domains\Compliance\Models\DpiaAssessment::query()->where('company_id', $this->company->id)->count());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSigned(array $payload)
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = app(SignatureValidator::class)->generate($body, $this->secret);

        return $this->call(
            'POST',
            '/api/v1/webhooks/incoming/shop-web',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_AMS_SIGNATURE' => $signature,
            ],
            $body
        );
    }
}
