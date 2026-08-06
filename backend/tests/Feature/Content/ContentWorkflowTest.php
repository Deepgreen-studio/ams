<?php

namespace Tests\Feature\Content;

use App\Domains\Content\Enums\ContentStatusSlug;
use App\Domains\Content\Enums\ContentTypeSlug;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentStatus;
use App\Domains\Content\Models\ContentType;
use App\Domains\Users\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\ContentFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ContentType $pageType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ContentFoundationSeeder::class);

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'cms-workflow@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::Active,
            'is_active' => true,
        ]);
        $this->admin->assignRole('super-admin');

        $this->pageType = ContentType::query()->where('slug', ContentTypeSlug::Page->value)->firstOrFail();
    }

    public function test_linear_workflow_submit_review_approve_publish_and_reject(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/content', [
            'content_type_id' => $this->pageType->uuid,
            'title' => 'Workflow Page',
            'body' => 'Needs approval',
            'status' => ContentStatusSlug::Draft->value,
        ])->assertCreated();

        $uuid = $create->json('data.content.uuid');

        $this->postJson("/api/v1/content/{$uuid}/publish")
            ->assertStatus(422);

        $this->postJson("/api/v1/content/{$uuid}/workflow/submit", [
            'comments' => 'Ready for editor',
        ])->assertOk()
            ->assertJsonPath('data.content.status.slug', 'pending_review');

        $this->postJson("/api/v1/content/{$uuid}/workflow/review", [
            'comments' => 'Looks good',
        ])->assertOk()
            ->assertJsonPath('data.content.status.slug', 'reviewed');

        $this->postJson("/api/v1/content/{$uuid}/workflow/approve", [
            'comments' => 'Manager approved',
        ])->assertOk()
            ->assertJsonPath('data.content.status.slug', 'approved')
            ->assertJsonPath('data.content.approver.email', 'cms-workflow@example.com');

        $this->postJson("/api/v1/content/{$uuid}/workflow/publish", [
            'comments' => 'Go live',
        ])->assertOk()
            ->assertJsonPath('data.content.status.slug', 'published');

        $history = $this->getJson("/api/v1/content/{$uuid}/workflow/history")
            ->assertOk()
            ->json('data.history');

        $this->assertGreaterThanOrEqual(4, count($history));
        $this->assertDatabaseCount('content_workflow_histories', 4);

        $second = $this->postJson('/api/v1/content', [
            'content_type_id' => $this->pageType->uuid,
            'title' => 'Reject Me',
            'body' => 'Nope',
            'status' => ContentStatusSlug::Draft->value,
        ])->assertCreated()->json('data.content.uuid');

        $this->postJson("/api/v1/content/{$second}/workflow/submit")->assertOk();
        $this->postJson("/api/v1/content/{$second}/workflow/reject", [
            'comments' => 'Needs rewrite',
        ])->assertOk()
            ->assertJsonPath('data.content.status.slug', 'rejected');

        $this->assertDatabaseHas('content_workflow_histories', [
            'action' => 'reject',
            'to_status' => 'rejected',
            'comments' => 'Needs rewrite',
        ]);
    }

    public function test_approval_queue_returns_items_for_actor_level(): void
    {
        Sanctum::actingAs($this->admin);

        $draftId = ContentStatus::query()->where('slug', ContentStatusSlug::Draft->value)->value('id');
        $pendingId = ContentStatus::query()->where('slug', ContentStatusSlug::PendingReview->value)->value('id');

        Content::factory()->create([
            'content_type_id' => $this->pageType->id,
            'content_status_id' => $draftId,
            'title' => 'Draft only',
            'created_by' => $this->admin->id,
        ]);
        Content::factory()->create([
            'content_type_id' => $this->pageType->id,
            'content_status_id' => $pendingId,
            'title' => 'Needs editor',
            'created_by' => $this->admin->id,
        ]);

        $queue = $this->getJson('/api/v1/content/workflow/queue')->assertOk();
        $titles = collect($queue->json('data.contents.items'))->pluck('title')->all();

        $this->assertContains('Needs editor', $titles);
        $this->assertNotContains('Draft only', $titles);
    }

    public function test_cannot_skip_linear_levels(): void
    {
        Sanctum::actingAs($this->admin);

        $uuid = $this->postJson('/api/v1/content', [
            'content_type_id' => $this->pageType->uuid,
            'title' => 'Skip levels',
            'body' => 'Illegal path',
            'status' => ContentStatusSlug::Draft->value,
        ])->assertCreated()->json('data.content.uuid');

        $this->postJson("/api/v1/content/{$uuid}/workflow/approve")
            ->assertStatus(422);

        $this->postJson("/api/v1/content/{$uuid}/workflow/submit")->assertOk();
        $this->postJson("/api/v1/content/{$uuid}/workflow/approve")
            ->assertStatus(422);
    }
}
