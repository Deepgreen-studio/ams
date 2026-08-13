<?php

namespace Tests\Feature\Support;

use App\Domains\Support\Enums\CannedResponseVisibility;
use App\Domains\Support\Models\SupportCannedResponse;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SupportCannedResponseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupportCannedResponseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $agent;

    private User $otherAgent;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'canned-admin@example.com']);
        $this->admin->assignRole('super-admin');

        Role::findOrCreate('support-agent');
        $this->agent = User::factory()->create(['email' => 'canned-agent@example.com']);
        $this->agent->assignRole('support-agent');
        $this->agent->givePermissionTo(['support.view', 'support.create', 'support.update', 'support.delete']);

        $this->otherAgent = User::factory()->create(['email' => 'canned-other@example.com']);
        $this->otherAgent->assignRole('support-agent');
        $this->otherAgent->givePermissionTo(['support.view', 'support.create', 'support.update', 'support.delete']);
    }

    public function test_agent_can_create_personal_canned_response(): void
    {
        Sanctum::actingAs($this->agent);

        $create = $this->postJson('/api/v1/support/canned-responses', [
            'title' => 'My thanks',
            'shortcut' => 'Thanks!',
            'body' => '<p>Thank you for waiting.</p>',
            'visibility' => 'personal',
        ])->assertCreated();

        $this->assertSame('personal', $create->json('data.response.visibility'));
        $this->assertSame('thanks', $create->json('data.response.shortcut'));

        $uuid = $create->json('data.response.uuid');

        $this->getJson('/api/v1/support/canned-responses/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.response.title', 'My thanks');
    }

    public function test_agent_cannot_create_shared_without_manage(): void
    {
        Sanctum::actingAs($this->agent);

        $this->postJson('/api/v1/support/canned-responses', [
            'title' => 'Team greeting',
            'body' => '<p>Hello</p>',
            'visibility' => 'shared',
        ])->assertForbidden();
    }

    public function test_admin_can_create_shared_and_agents_can_list_it(): void
    {
        Sanctum::actingAs($this->admin);

        $uuid = $this->postJson('/api/v1/support/canned-responses', [
            'title' => 'Shared greeting',
            'body' => '<p>Hello from the team</p>',
            'visibility' => 'shared',
        ])->assertCreated()->json('data.response.uuid');

        Sanctum::actingAs($this->agent);

        $this->getJson('/api/v1/support/canned-responses?visibility=shared')
            ->assertOk()
            ->assertJsonPath('data.responses.meta.total', 1)
            ->assertJsonPath('data.responses.items.0.uuid', $uuid);

        $this->getJson('/api/v1/support/canned-responses?per_page=1&page=1')
            ->assertOk()
            ->assertJsonPath('data.responses.meta.per_page', 1)
            ->assertJsonPath('data.responses.meta.current_page', 1);
    }

    public function test_personal_responses_are_private_to_owner(): void
    {
        Sanctum::actingAs($this->agent);

        $uuid = $this->postJson('/api/v1/support/canned-responses', [
            'title' => 'Private note',
            'body' => '<p>Only me</p>',
            'visibility' => 'personal',
        ])->assertCreated()->json('data.response.uuid');

        Sanctum::actingAs($this->otherAgent);

        $this->getJson('/api/v1/support/canned-responses/'.$uuid)
            ->assertForbidden();

        $this->getJson('/api/v1/support/canned-responses?visibility=personal')
            ->assertOk()
            ->assertJsonPath('data.responses.meta.total', 0);
    }

    public function test_owner_can_update_and_delete_personal_response(): void
    {
        Sanctum::actingAs($this->agent);

        $uuid = $this->postJson('/api/v1/support/canned-responses', [
            'title' => 'Draft',
            'body' => '<p>v1</p>',
            'visibility' => 'personal',
        ])->assertCreated()->json('data.response.uuid');

        $this->putJson('/api/v1/support/canned-responses/'.$uuid, [
            'title' => 'Updated draft',
            'body' => '<p>v2</p>',
        ])
            ->assertOk()
            ->assertJsonPath('data.response.title', 'Updated draft');

        $this->deleteJson('/api/v1/support/canned-responses/'.$uuid)
            ->assertOk();

        $this->assertSoftDeleted('support_canned_responses', ['uuid' => $uuid]);
    }

    public function test_use_endpoint_increments_usage_count(): void
    {
        Sanctum::actingAs($this->admin);

        $uuid = $this->postJson('/api/v1/support/canned-responses', [
            'title' => 'Reusable',
            'body' => '<p>Reuse me</p>',
            'visibility' => 'shared',
        ])->assertCreated()->json('data.response.uuid');

        Sanctum::actingAs($this->agent);

        $this->postJson('/api/v1/support/canned-responses/'.$uuid.'/use')
            ->assertOk()
            ->assertJsonPath('data.response.usage_count', 1);

        $this->assertSame(1, SupportCannedResponse::query()->where('uuid', $uuid)->value('usage_count'));
    }

    public function test_dashboard_and_seeder(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seed(SupportCannedResponseSeeder::class);

        $this->getJson('/api/v1/support/canned-responses/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'statistics' => ['total', 'personal', 'shared', 'active'],
                    'recent',
                ],
            ]);

        $this->assertGreaterThan(0, SupportCannedResponse::query()
            ->where('visibility', CannedResponseVisibility::Shared->value)
            ->count());
    }
}
