<?php

namespace Tests\Feature\Content;

use App\Domains\Content\Enums\ContentStatusSlug;
use App\Domains\Content\Enums\ContentTypeSlug;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentStatus;
use App\Domains\Content\Models\ContentType;
use App\Domains\Content\Models\ContentVersion;
use App\Domains\Users\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\ContentFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentVersionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ContentType $pageType;

    private ContentStatus $draftStatus;

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
            'email' => 'cms-versions@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::Active,
            'is_active' => true,
        ]);
        $this->admin->assignRole('super-admin');

        $this->pageType = ContentType::query()->where('slug', ContentTypeSlug::Page->value)->firstOrFail();
        $this->draftStatus = ContentStatus::query()->where('slug', ContentStatusSlug::Draft->value)->firstOrFail();
    }

    public function test_create_update_publish_create_versions_but_autosave_does_not(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/content', [
            'content_type_id' => $this->pageType->uuid,
            'title' => 'Versioned Page',
            'body' => 'Body v1',
            'status' => ContentStatusSlug::Draft->value,
            'reason' => 'Initial draft',
        ])->assertCreated();

        $uuid = $create->json('data.content.uuid');
        $this->assertSame(1, $create->json('data.content.version'));
        $this->assertDatabaseCount('content_versions', 1);
        $this->assertDatabaseHas('content_versions', [
            'version' => 1,
            'reason' => 'Initial draft',
            'status' => 'draft',
        ]);

        $this->putJson("/api/v1/content/{$uuid}", [
            'title' => 'Versioned Page Updated',
            'body' => 'Body v2',
            'reason' => 'Copy edit',
        ])->assertOk()
            ->assertJsonPath('data.content.version', 2);

        $this->assertDatabaseCount('content_versions', 2);

        $this->postJson("/api/v1/content/{$uuid}/autosave", [
            'title' => 'Autosaved title',
            'body' => 'Autosaved body',
        ])->assertOk();

        $this->assertDatabaseCount('content_versions', 2);
        $this->assertDatabaseHas('contents', [
            'uuid' => $uuid,
            'title' => 'Autosaved title',
            'version' => 2,
        ]);

        $this->postJson("/api/v1/content/{$uuid}/workflow/submit")->assertOk();
        $this->postJson("/api/v1/content/{$uuid}/workflow/review")->assertOk();
        $this->postJson("/api/v1/content/{$uuid}/workflow/approve")->assertOk();
        $this->postJson("/api/v1/content/{$uuid}/workflow/publish")->assertOk()
            ->assertJsonPath('data.content.status.slug', 'published');

        $this->assertDatabaseHas('content_versions', [
            'reason' => 'Published via approval workflow',
            'status' => 'published',
        ]);
    }

    public function test_can_list_compare_and_restore_versions(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/content', [
            'content_type_id' => $this->pageType->uuid,
            'title' => 'Restore Me',
            'body' => 'Original body',
            'status' => ContentStatusSlug::Draft->value,
        ])->assertCreated();

        $uuid = $create->json('data.content.uuid');

        $this->putJson("/api/v1/content/{$uuid}", [
            'title' => 'Changed Title',
            'body' => 'Changed body',
            'reason' => 'Rewrite',
        ])->assertOk();

        $list = $this->getJson("/api/v1/content/{$uuid}/versions")->assertOk();
        $versions = $list->json('data.versions');
        $this->assertCount(2, $versions);

        $v1 = collect($versions)->firstWhere('version', 1);
        $v2 = collect($versions)->firstWhere('version', 2);
        $this->assertNotNull($v1);
        $this->assertNotNull($v2);

        $compare = $this->getJson("/api/v1/content/{$uuid}/versions/compare?from={$v1['uuid']}&to={$v2['uuid']}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $changed = $compare->json('data.comparison.changed_fields');
        $this->assertContains('title', $changed);
        $this->assertContains('body', $changed);

        $this->postJson("/api/v1/content/{$uuid}/versions/{$v1['uuid']}/restore", [
            'reason' => 'Roll back rewrite',
        ])->assertOk()
            ->assertJsonPath('data.content.title', 'Restore Me')
            ->assertJsonPath('data.content.body', 'Original body')
            ->assertJsonPath('data.content.version', 3);

        $this->assertDatabaseCount('content_versions', 3);
        $this->assertDatabaseHas('content_versions', [
            'version' => 3,
            'reason' => 'Roll back rewrite',
        ]);

        $restoredSnapshot = ContentVersion::query()->where('version', 3)->firstOrFail();
        $this->assertSame('Restore Me', $restoredSnapshot->snapshot['title'] ?? null);
    }

    public function test_legacy_content_gets_baseline_before_update(): void
    {
        Sanctum::actingAs($this->admin);

        $content = Content::factory()->create([
            'content_type_id' => $this->pageType->id,
            'content_status_id' => $this->draftStatus->id,
            'title' => 'Legacy Entry',
            'body' => 'Legacy body',
            'version' => 1,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->assertDatabaseCount('content_versions', 0);

        $this->putJson("/api/v1/content/{$content->uuid}", [
            'title' => 'Legacy Updated',
            'reason' => 'First tracked change',
        ])->assertOk()
            ->assertJsonPath('data.content.version', 2);

        $this->assertDatabaseCount('content_versions', 2);
        $this->assertDatabaseHas('content_versions', [
            'content_id' => $content->id,
            'version' => 1,
            'reason' => 'Baseline snapshot',
        ]);
        $this->assertDatabaseHas('content_versions', [
            'content_id' => $content->id,
            'version' => 2,
            'reason' => 'First tracked change',
        ]);
    }
}
