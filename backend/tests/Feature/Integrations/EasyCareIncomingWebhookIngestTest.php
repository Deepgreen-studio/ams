<?php

namespace Tests\Feature\Integrations;

use App\Domains\Applications\Enums\ApplicationCategory;
use App\Domains\Applications\Enums\ApplicationPlatform;
use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Enums\ApplicationVisibility;
use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Integrations\Enums\WebhookDirection;
use App\Domains\Integrations\Enums\WebhookStatus;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use App\Shared\Services\Webhook\SignatureValidator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EasyCareIncomingWebhookIngestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private Webhook $webhook;

    private string $secret = 'easycare-ams-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'admin@ams.test']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'EasyCare',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'GBP',
        ]);

        Application::query()->create([
            'company_id' => $this->company->id,
            'slug' => 'easycare-web',
            'name' => 'EasyCare',
            'platform' => ApplicationPlatform::Web,
            'category' => ApplicationCategory::Health,
            'status' => ApplicationStatus::Active,
            'visibility' => ApplicationVisibility::Internal,
            'current_version' => '1.0.0',
            'minimum_supported_version' => '1.0.0',
        ]);

        $this->webhook = Webhook::query()->create([
            'company_id' => $this->company->id,
            'name' => 'EasyCare Incoming',
            'slug' => 'easycare',
            'direction' => WebhookDirection::Incoming,
            'status' => WebhookStatus::Active,
            'secret' => $this->secret,
            'signature_algorithm' => 'hmac_sha256',
            'signature_header' => 'X-EasyCare-Signature',
            'subscribed_events' => ['patient.created', 'user.created', 'easycare.test'],
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_patient_created_creates_support_ticket_and_compliance_privacy_request(): void
    {
        $payload = [
            'event' => 'patient.created',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => 42,
                'uuid' => '11111111-1111-4111-8111-111111111111',
                'user_id' => 7,
                'medical_record_number' => 'MRN-42',
                'has_diabetes' => true,
                'diabetes_type' => 'type2',
            ],
        ];

        $response = $this->postSignedIncoming($payload);

        $response->assertOk()
            ->assertJsonPath('data.received', true)
            ->assertJsonPath('data.event_name', 'patient.created')
            ->assertJsonPath('data.ingest.handled', true)
            ->assertJsonPath('data.ingest.skipped', false);

        $ticket = SupportTicket::query()
            ->where('company_id', $this->company->id)
            ->where('description', 'like', '%[easycare-ingest:patient.created:11111111-1111-4111-8111-111111111111]%')
            ->first();

        $this->assertNotNull($ticket);
        $this->assertTrue((bool) $ticket->involves_personal_data);
        $this->assertSame('api', $ticket->source->value);
        $this->assertNotNull($ticket->privacy_request_id);
        $this->assertNotNull($ticket->compliance_routed_at);

        $privacy = PrivacyRequest::query()->findOrFail($ticket->privacy_request_id);
        $this->assertSame($ticket->id, $privacy->support_ticket_id);
        $this->assertSame($response->json('data.ingest.support_ticket_uuid'), $ticket->uuid);
        $this->assertSame($response->json('data.ingest.privacy_request_uuid'), $privacy->uuid);
    }

    public function test_user_created_creates_support_ticket_without_compliance_route(): void
    {
        $payload = [
            'event' => 'user.created',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => 9,
                'uuid' => '22222222-2222-4222-8222-222222222222',
                'name' => 'Jane Doe',
                'email' => 'jane@easycare.test',
                'role' => 'patient',
            ],
        ];

        $this->postSignedIncoming($payload)
            ->assertOk()
            ->assertJsonPath('data.ingest.handled', true);

        $ticket = SupportTicket::query()
            ->where('company_id', $this->company->id)
            ->where('description', 'like', '%[easycare-ingest:user.created:22222222-2222-4222-8222-222222222222]%')
            ->first();

        $this->assertNotNull($ticket);
        $this->assertFalse((bool) $ticket->involves_personal_data);
        $this->assertNull($ticket->privacy_request_id);
        $this->assertSame(0, PrivacyRequest::query()->where('support_ticket_id', $ticket->id)->count());
    }

    public function test_duplicate_easycare_event_is_idempotent(): void
    {
        $payload = [
            'event' => 'easycare.test',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'uuid' => '33333333-3333-4333-8333-333333333333',
                'message' => 'Test webhook from EasyCare dashboard',
                'source' => 'easycare-dashboard',
            ],
        ];

        $this->postSignedIncoming($payload)->assertOk()->assertJsonPath('data.ingest.skipped', false);
        $this->postSignedIncoming($payload)
            ->assertOk()
            ->assertJsonPath('data.ingest.skipped', true)
            ->assertJsonPath('data.ingest.actions.0', 'idempotent_skip');

        $this->assertSame(1, SupportTicket::query()->where('company_id', $this->company->id)->count());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSignedIncoming(array $payload)
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = app(SignatureValidator::class)->generate($body, $this->secret);

        return $this->call(
            'POST',
            '/api/v1/webhooks/incoming/easycare',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_EASYCARE_SIGNATURE' => $signature,
            ],
            $body
        );
    }
}
