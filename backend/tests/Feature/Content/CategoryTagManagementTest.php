<?php

namespace Tests\Feature\Content;

use App\Domains\Content\Models\Content;
use App\Domains\Content\Models\ContentCategory;
use App\Domains\Content\Models\ContentTag;
use App\Domains\Users\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\ContentFoundationSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTagManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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
            'email' => 'taxonomy-admin@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::Active,
            'is_active' => true,
        ]);
        $this->admin->assignRole('super-admin');
    }

    public function test_admin_can_manage_nested_categories_with_tree_and_bulk(): void
    {
        Sanctum::actingAs($this->admin);

        $parent = $this->postJson('/api/v1/content/categories', [
            'name' => 'Legal',
            'slug' => 'legal',
            'seo_title' => 'Legal SEO',
            'sort_order' => 10,
        ])->assertCreated()
            ->json('data.category');

        $child = $this->postJson('/api/v1/content/categories', [
            'name' => 'Privacy',
            'parent_id' => $parent['uuid'],
            'sort_order' => 20,
        ])->assertCreated()
            ->json('data.category');

        $this->assertSame($parent['id'], ContentCategory::query()->where('uuid', $child['uuid'])->value('parent_id'));

        $this->getJson('/api/v1/content/categories/tree')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tree.0.slug', 'legal')
            ->assertJsonPath('data.tree.0.children.0.slug', 'privacy');

        $this->getJson('/api/v1/content/categories?search=Legal')
            ->assertOk()
            ->assertJsonPath('data.categories.meta.total', 1);

        $this->postJson('/api/v1/content/categories/bulk', [
            'action' => 'deactivate',
            'ids' => [$child['uuid']],
        ])->assertOk()->assertJsonPath('data.affected', 1);

        $this->assertFalse((bool) ContentCategory::query()->where('uuid', $child['uuid'])->value('is_active'));

        $this->deleteJson('/api/v1/content/categories/'.$parent['uuid'])
            ->assertStatus(422);

        $this->deleteJson('/api/v1/content/categories/'.$child['uuid'])
            ->assertOk();

        $this->assertSoftDeleted('content_categories', ['uuid' => $child['uuid']]);
    }

    public function test_admin_can_manage_tags_with_search_filter_and_bulk(): void
    {
        Sanctum::actingAs($this->admin);

        $tag = $this->postJson('/api/v1/content/tags', [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'seo_title' => 'Enterprise Tag',
            'sort_order' => 5,
            'is_active' => true,
        ])->assertCreated()
            ->json('data.tag');

        $this->putJson('/api/v1/content/tags/'.$tag['uuid'], [
            'name' => 'Enterprise CMS',
            'description' => 'Updated',
        ])->assertOk()
            ->assertJsonPath('data.tag.name', 'Enterprise CMS');

        $this->getJson('/api/v1/content/tags?search=Enterprise&status=active')
            ->assertOk()
            ->assertJsonPath('data.tags.meta.total', 1);

        ContentTag::factory()->count(2)->create();

        $second = ContentTag::factory()->create(['name' => 'Mobile']);

        $this->postJson('/api/v1/content/tags/bulk', [
            'action' => 'delete',
            'ids' => [$tag['uuid'], $second->uuid],
        ])->assertOk()->assertJsonPath('data.affected', 2);

        $this->assertSoftDeleted('content_tags', ['uuid' => $tag['uuid']]);
    }

    public function test_content_can_sync_multiple_categories_via_pivot(): void
    {
        Sanctum::actingAs($this->admin);

        $a = ContentCategory::factory()->create(['name' => 'Alpha']);
        $b = ContentCategory::factory()->create(['name' => 'Beta']);
        $typeUuid = \App\Domains\Content\Models\ContentType::query()->where('slug', 'page')->value('uuid');

        $response = $this->postJson('/api/v1/content', [
            'content_type_id' => $typeUuid,
            'title' => 'Multi Category Page',
            'categories' => [$a->uuid, $b->uuid],
            'tags' => ['launch'],
        ])->assertCreated();

        $contentUuid = $response->json('data.content.uuid');
        $content = Content::query()->where('uuid', $contentUuid)->firstOrFail();

        $this->assertDatabaseCount('content_category', 2);
        $this->assertDatabaseCount('content_tag', 1);
        $this->assertSame($a->id, $content->content_category_id);
        $this->assertCount(2, $content->categories);
    }

    public function test_category_cannot_set_descendant_as_parent(): void
    {
        Sanctum::actingAs($this->admin);

        $parent = ContentCategory::factory()->create(['name' => 'Root']);
        $child = ContentCategory::factory()->childOf($parent)->create(['name' => 'Child']);

        $this->putJson('/api/v1/content/categories/'.$parent->uuid, [
            'parent_id' => $child->uuid,
        ])->assertStatus(422);
    }
}
