<?php

namespace Tests\Feature\Ai;

use App\Domains\Ai\Enums\AiProviderDriver;
use App\Domains\Ai\Models\AiProvider;
use App\Models\User;
use Database\Seeders\AiSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'ai-admin@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_admin_can_list_providers_and_view_dashboard(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/ai/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'provider_statistics',
                    'prompt_statistics',
                    'usage_statistics',
                    'catalog',
                ],
            ]);

        $this->getJson('/api/v1/ai/providers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.providers.items.0.driver', AiProviderDriver::Null->value);
    }

    public function test_admin_can_create_provider_without_hardcoding_driver_class(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/ai/providers', [
            'name' => 'OpenAI Production',
            'driver' => AiProviderDriver::OpenAi->value,
            'default_model' => 'gpt-4o-mini',
            'is_enabled' => true,
            'is_default' => false,
            'credentials' => ['api_key' => 'sk-test'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.provider.driver', 'openai');

        $this->assertDatabaseHas('ai_providers', [
            'name' => 'OpenAI Production',
            'driver' => 'openai',
        ]);
    }

    public function test_chat_assistant_uses_provider_abstraction(): void
    {
        Sanctum::actingAs($this->admin);

        $provider = AiProvider::query()->where('slug', 'local-null')->firstOrFail();

        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'How do I configure AI providers?',
            'provider_id' => $provider->uuid,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'conversation' => ['uuid', 'messages'],
                    'reply',
                ],
            ]);

        $this->assertDatabaseHas('ai_conversations', [
            'user_id' => $this->admin->id,
            'ai_provider_id' => $provider->id,
        ]);

        $this->assertDatabaseHas('ai_usage_logs', [
            'feature' => 'chat_assistant',
            'operation' => 'chat',
            'status' => 'success',
            'driver' => 'null',
        ]);
    }

    public function test_feature_endpoints_work_with_null_driver(): void
    {
        Sanctum::actingAs($this->admin);
        $provider = AiProvider::query()->where('slug', 'local-null')->firstOrFail();

        $this->postJson('/api/v1/ai/features/categorize', [
            'text' => 'Billing invoice overdue for customer',
            'labels' => ['billing', 'technical', 'sales'],
            'provider_id' => $provider->uuid,
        ])->assertOk()->assertJsonPath('success', true);

        $this->postJson('/api/v1/ai/features/route-ticket', [
            'subject' => 'Cannot login',
            'description' => 'Password reset failed on mobile app',
            'teams' => ['support', 'technical', 'billing'],
            'provider_id' => $provider->uuid,
        ])->assertOk()->assertJsonPath('success', true);

        $this->postJson('/api/v1/ai/features/summarize', [
            'text' => str_repeat('Enterprise release notes. ', 20),
            'provider_id' => $provider->uuid,
        ])->assertOk()->assertJsonPath('success', true);

        $this->postJson('/api/v1/ai/features/translate', [
            'text' => 'Welcome to AMS',
            'target_locale' => 'bn',
            'provider_id' => $provider->uuid,
        ])->assertOk()->assertJsonPath('success', true);

        $this->postJson('/api/v1/ai/features/search', [
            'query' => 'password reset',
            'documents' => [
                'How to reset password in AMS',
                'Invoice generation guide',
                'Release deployment checklist',
            ],
            'provider_id' => $provider->uuid,
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_prompt_manager_crud_and_publish(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/ai/prompts', [
            'name' => 'Custom Suggest Prompt',
            'feature' => 'suggestions',
            'system_prompt' => 'Suggest carefully.',
            'user_template' => '{{text}}',
            'status' => 'draft',
        ]);

        $create->assertCreated()->assertJsonPath('data.prompt.status', 'draft');
        $uuid = $create->json('data.prompt.uuid');

        $this->postJson("/api/v1/ai/prompts/{$uuid}/publish")
            ->assertOk()
            ->assertJsonPath('data.prompt.status', 'published');
    }

    public function test_provider_connection_test_uses_manager(): void
    {
        Sanctum::actingAs($this->admin);
        $provider = AiProvider::query()->where('slug', 'local-null')->firstOrFail();

        $this->postJson("/api/v1/ai/providers/{$provider->uuid}/test")
            ->assertOk()
            ->assertJsonPath('data.result.ok', true)
            ->assertJsonPath('data.result.driver', 'null');
    }
}
