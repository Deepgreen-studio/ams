<?php

namespace Tests\Feature\Content;

use App\Domains\Content\Enums\ContentStatusSlug;
use App\Domains\Content\Enums\ContentTypeSlug;
use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentCategory;
use App\Domains\Content\Models\ContentStatus;
use App\Domains\Content\Models\ContentTag;
use App\Domains\Content\Models\ContentType;
use App\Domains\Users\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\ContentFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ContentType $pageType;

    private ContentStatus $draftStatus;

    private ContentStatus $publishedStatus;

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
            'email' => 'cms-admin@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::Active,
            'is_active' => true,
        ]);
        $this->admin->assignRole('super-admin');

        $this->pageType = ContentType::query()->where('slug', ContentTypeSlug::Page->value)->firstOrFail();
        $this->draftStatus = ContentStatus::query()->where('slug', ContentStatusSlug::Draft->value)->firstOrFail();
        $this->publishedStatus = ContentStatus::query()->where('slug', ContentStatusSlug::Published->value)->firstOrFail();
    }

    public function test_guest_cannot_list_content(): void
    {
        $this->getJson('/api/v1/content')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_list_content_with_pagination_and_statistics(): void
    {
        Content::factory()->count(3)->create([
            'content_type_id' => $this->pageType->id,
            'content_status_id' => $this->draftStatus->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/content?per_page=2');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'contents' => [
                        'items',
                        'meta' => ['current_page', 'per_page', 'total'],
                        'links',
                    ],
                    'statistics' => ['total', 'draft', 'pending_review', 'reviewed', 'approved', 'rejected', 'published', 'scheduled', 'archived', 'featured', 'trashed'],
                ],
            ]);

        $this->assertSame(2, count($response->json('data.contents.items')));
    }

    public function test_admin_can_create_update_publish_and_delete_content(): void
    {
        Sanctum::actingAs($this->admin);

        $category = ContentCategory::factory()->create(['name' => 'Announcements']);
        $tag = ContentTag::factory()->create(['name' => 'Launch']);

        $create = $this->postJson('/api/v1/content', [
            'content_type_id' => $this->pageType->uuid,
            'content_category_id' => $category->uuid,
            'title' => 'Welcome Page',
            'slug' => 'welcome-page',
            'excerpt' => 'Enterprise welcome content',
            'body' => '<p>Hello world</p>',
            'status' => 'draft',
            'tags' => [$tag->uuid, 'mobile'],
            'is_featured' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content.title', 'Welcome Page')
            ->assertJsonPath('data.content.status.slug', 'draft');

        $uuid = $create->json('data.content.uuid');
        $this->assertDatabaseHas('contents', [
            'uuid' => $uuid,
            'title' => 'Welcome Page',
            'content_type_id' => $this->pageType->id,
        ]);
        $this->assertDatabaseCount('content_tag', 2);

        $update = $this->putJson('/api/v1/content/'.$uuid, [
            'title' => 'Welcome Page Updated',
            'body' => '<p>Updated</p>',
        ]);

        $update->assertOk()
            ->assertJsonPath('data.content.title', 'Welcome Page Updated');

        $this->postJson("/api/v1/content/{$uuid}/workflow/submit")->assertOk();
        $this->postJson("/api/v1/content/{$uuid}/workflow/review")->assertOk();
        $this->postJson("/api/v1/content/{$uuid}/workflow/approve")->assertOk();
        $publish = $this->postJson('/api/v1/content/'.$uuid.'/workflow/publish');
        $publish->assertOk()
            ->assertJsonPath('data.content.status.slug', 'published');

        $this->assertDatabaseHas('contents', [
            'uuid' => $uuid,
            'content_status_id' => $this->publishedStatus->id,
        ]);

        $unpublish = $this->postJson('/api/v1/content/'.$uuid.'/unpublish');
        $unpublish->assertOk()
            ->assertJsonPath('data.content.status.slug', 'draft');

        $delete = $this->deleteJson('/api/v1/content/'.$uuid);
        $delete->assertOk()->assertJsonPath('success', true);
        $this->assertSoftDeleted('contents', ['uuid' => $uuid]);

        $restore = $this->postJson('/api/v1/content/'.$uuid.'/restore');
        $restore->assertOk()->assertJsonPath('data.content.uuid', $uuid);
    }

    public function test_admin_can_filter_content_by_type_and_search(): void
    {
        $blogType = ContentType::query()->where('slug', ContentTypeSlug::Blog->value)->firstOrFail();

        Content::factory()->create([
            'content_type_id' => $this->pageType->id,
            'content_status_id' => $this->draftStatus->id,
            'title' => 'Alpha Privacy',
            'slug' => 'alpha-privacy',
        ]);
        Content::factory()->create([
            'content_type_id' => $blogType->id,
            'content_status_id' => $this->draftStatus->id,
            'title' => 'Hidden Blog',
            'slug' => 'hidden-blog',
        ]);

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/content?type=page&search=Alpha')
            ->assertOk()
            ->assertJsonPath('data.contents.meta.total', 1)
            ->assertJsonPath('data.contents.items.0.slug', 'alpha-privacy');
    }

    public function test_admin_can_manage_catalog_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/content/types')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/content/statuses')
            ->assertOk()
            ->assertJsonPath('success', true);

        $type = $this->postJson('/api/v1/content/types', [
            'name' => 'Press Release',
            'slug' => 'press-release',
        ]);
        $type->assertCreated()->assertJsonPath('data.type.slug', 'press-release');

        $category = $this->postJson('/api/v1/content/categories', [
            'name' => 'Legal',
        ]);
        $category->assertCreated()->assertJsonPath('data.category.name', 'Legal');

        $tag = $this->postJson('/api/v1/content/tags', [
            'name' => 'Enterprise',
        ]);
        $tag->assertCreated()->assertJsonPath('data.tag.name', 'Enterprise');
    }

    public function test_user_without_permission_cannot_create_content(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Active,
            'is_active' => true,
        ]);
        $user->assignRole('read-only-user');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/content', [
            'content_type_id' => $this->pageType->uuid,
            'title' => 'Denied',
        ])->assertForbidden();
    }
}
