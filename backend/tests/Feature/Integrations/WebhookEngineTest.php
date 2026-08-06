<?php

namespace Tests\Feature\Integrations;

use App\Domains\Companies\Models\Company;
use App\Domains\Integrations\Models\Webhook;
use App\Domains\Integrations\Models\WebhookLog;
use App\Models\User;
use App\Shared\Services\Webhook\SignatureValidator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WebhookEventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebhookEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(WebhookEventSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'webhook-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Webhook Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_admin_can_create_and_list_webhooks(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/webhooks', [
            'company_id' => $this->company->uuid,
            'name' => 'CRM Outbound',
            'direction' => 'outgoing',
            'status' => 'active',
            'url' => 'https://hooks.example.test/crm',
            'subscribed_events' => ['integration.created', 'webhook.test'],
            'signature_algorithm' => 'hmac_sha256',
        ])->assertCreated()
            ->assertJsonPath('data.webhook.name', 'CRM Outbound')
            ->assertJsonPath('data.webhook.has_secret', true)
            ->assertJsonMissingPath('data.webhook.secret');

        $this->getJson('/api/v1/webhooks?search=CRM')
            ->assertOk()
            ->assertJsonPath('data.webhooks.meta.total', 1);

        $this->assertNotEmpty($create->json('data.webhook.uuid'));
    }

    public function test_outgoing_webhook_test_delivers_signed_payload(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'hooks.example.test/*' => Http::response(['accepted' => true], 200),
        ]);

        $webhook = Webhook::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Signed Hook',
            'slug' => 'signed-hook',
            'direction' => 'outgoing',
            'status' => 'active',
            'url' => 'https://hooks.example.test/events',
            'secret' => 'super-secret',
            'signature_algorithm' => 'hmac_sha256',
            'signature_header' => 'X-AMS-Signature',
            'subscribed_events' => ['webhook.test'],
            'timeout' => 20,
            'retry_attempts' => 2,
        ]);

        $this->postJson('/api/v1/webhooks/'.$webhook->uuid.'/test', [
            'event_name' => 'webhook.test',
            'payload' => ['hello' => 'world'],
        ])
            ->assertOk()
            ->assertJsonPath('data.log.status', 'success');

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-AMS-Signature')
                && str_contains($request->url(), '/events');
        });
    }

    public function test_failed_outgoing_webhook_can_be_retried(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake([
            'hooks.example.test/fail' => Http::sequence()
                ->push(['error' => true], 500)
                ->push(['ok' => true], 200),
        ]);

        $webhook = Webhook::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Retry Hook',
            'slug' => 'retry-hook',
            'direction' => 'outgoing',
            'status' => 'active',
            'url' => 'https://hooks.example.test/fail',
            'secret' => 'secret',
            'signature_algorithm' => 'hmac_sha256',
            'subscribed_events' => ['webhook.test'],
            'retry_attempts' => 1,
        ]);

        $failed = $this->postJson('/api/v1/webhooks/'.$webhook->uuid.'/test', [
            'payload' => ['n' => 1],
        ])->assertOk()->json('data.log');

        $this->assertContains($failed['status'], ['failed', 'retrying']);

        WebhookLog::query()->where('uuid', $failed['uuid'])->update([
            'status' => 'failed',
            'max_attempts' => 1,
            'attempts' => 1,
        ]);

        $this->postJson('/api/v1/webhooks/logs/'.$failed['uuid'].'/retry')
            ->assertOk()
            ->assertJsonPath('data.log.status', 'success');
    }

    public function test_incoming_webhook_verifies_signature(): void
    {
        $secret = 'incoming-secret';
        $webhook = Webhook::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Incoming Hook',
            'slug' => 'incoming-hook',
            'direction' => 'incoming',
            'status' => 'active',
            'secret' => $secret,
            'signature_algorithm' => 'hmac_sha256',
            'signature_header' => 'X-AMS-Signature',
            'subscribed_events' => [],
        ]);

        $payload = json_encode(['event' => 'incoming.webhook', 'data' => ['id' => 1]], JSON_THROW_ON_ERROR);
        $signature = app(SignatureValidator::class)->generate($payload, $secret);

        $this->call(
            'POST',
            '/api/v1/webhooks/incoming/'.$webhook->uuid,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_AMS_SIGNATURE' => $signature,
            ],
            $payload
        )->assertOk()
            ->assertJsonPath('data.received', true);

        $this->assertDatabaseHas('webhook_logs', [
            'webhook_id' => $webhook->id,
            'direction' => 'incoming',
            'status' => 'success',
        ]);
    }

    public function test_incoming_webhook_rejects_invalid_signature(): void
    {
        $webhook = Webhook::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Secure Incoming',
            'slug' => 'secure-incoming',
            'direction' => 'incoming',
            'status' => 'active',
            'secret' => 'real-secret',
            'signature_algorithm' => 'hmac_sha256',
            'signature_header' => 'X-AMS-Signature',
        ]);

        $this->postJson('/api/v1/webhooks/incoming/'.$webhook->uuid, ['hello' => true], [
            'X-AMS-Signature' => 'sha256=invalid',
        ])->assertStatus(401);
    }

    public function test_webhook_events_are_listable(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/webhooks/events')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertGreaterThan(0, $this->getJson('/api/v1/webhooks/events')->json('data.events.meta.total'));
    }

    public function test_webhook_logs_are_listable(): void
    {
        Sanctum::actingAs($this->admin);
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $webhook = Webhook::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Log Hook',
            'slug' => 'log-hook',
            'direction' => 'outgoing',
            'status' => 'active',
            'url' => 'https://hooks.example.test/log',
            'secret' => 'secret',
            'signature_algorithm' => 'hmac_sha256',
            'subscribed_events' => ['webhook.test'],
            'retry_attempts' => 1,
        ]);

        $this->postJson('/api/v1/webhooks/'.$webhook->uuid.'/test')->assertOk();

        $this->getJson('/api/v1/webhooks/logs?webhook='.$webhook->uuid)
            ->assertOk()
            ->assertJsonPath('data.logs.meta.total', 1);
    }
}
