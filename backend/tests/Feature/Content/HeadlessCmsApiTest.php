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

class HeadlessCmsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ContentType $pageType;

    private ContentStatus $draftStatus;

    private ContentStatus $publishedStatus;

    private Content $published;

    private Content $draft;

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
            'email' => 'cms-headless@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::Active,
            'is_active' => true,
        ]);
        $this->admin->assignRole('super-admin');

        $this->pageType = ContentType::query()->where('slug', ContentTypeSlug::Page->value)->firstOrFail();
        $this->draftStatus = ContentStatus::query()->where('slug', ContentStatusSlug::Draft->value)->firstOrFail();
        $this->publishedStatus = ContentStatus::query()->where('slug', ContentStatusSlug::Published->value)->firstOrFail();

        $category = ContentCategory::factory()->create(['name' => 'Guides', 'slug' => 'guides', 'is_active' => true]);
        $tag = ContentTag::factory()->create(['name' => 'Enterprise', 'slug' => 'enterprise']);

        $this->published = Content::factory()->published()->create([
            'content_type_id' => $this->pageType->id,
            'content_status_id' => $this->publishedStatus->id,
            'title' => 'Enterprise Guide',
            'slug' => 'enterprise-guide',
            'seo_title' => 'Enterprise Guide SEO',
            'seo_description' => 'SEO description for enterprise guide',
            'canonical_url' => 'https://example.com/content/page/enterprise-guide',
            'og_title' => 'OG Enterprise Guide',
            'is_featured' => true,
            'view_count' => 5,
            'published_at' => now()->subDay(),
        ]);
        $this->published->categories()->sync([$category->id]);
        $this->published->tags()->sync([$tag->id]);

        $this->draft = Content::factory()->create([
            'content_type_id' => $this->pageType->id,
            'content_status_id' => $this->draftStatus->id,
            'title' => 'Secret Draft',
            'slug' => 'secret-draft',
        ]);
    }

    public function test_public_api_lists_only_published_content(): void
    {
        $response = $this->getJson('/api/v1/cms/public/contents');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $titles = collect($response->json('data.contents.items'))->pluck('title')->all();
        $this->assertContains('Enterprise Guide', $titles);
        $this->assertNotContains('Secret Draft', $titles);
    }

    public function test_public_show_returns_seo_package_and_increments_views(): void
    {
        $before = (int) $this->published->view_count;

        $response = $this->getJson('/api/v1/cms/public/contents/'.$this->published->uuid);

        $response->assertOk()
            ->assertJsonPath('data.content.title', 'Enterprise Guide')
            ->assertJsonPath('data.content.seo.title', 'Enterprise Guide SEO')
            ->assertJsonPath('data.content.seo.open_graph.title', 'OG Enterprise Guide')
            ->assertJsonPath('data.content.seo.twitter_card.card', 'summary_large_image')
            ->assertJsonStructure([
                'data' => [
                    'content' => [
                        'seo' => [
                            'canonical_url',
                            'open_graph',
                            'twitter_card',
                            'schema_org',
                        ],
                    ],
                ],
            ]);

        $this->assertSame($before + 1, (int) $this->published->fresh()->view_count);
    }

    public function test_public_api_hides_drafts(): void
    {
        $this->getJson('/api/v1/cms/public/contents/'.$this->draft->uuid)
            ->assertNotFound();
    }

    public function test_public_search_featured_latest_and_popular(): void
    {
        $this->getJson('/api/v1/cms/public/search?q=Enterprise')
            ->assertOk()
            ->assertJsonPath('data.contents.items.0.title', 'Enterprise Guide');

        $this->getJson('/api/v1/cms/public/featured')
            ->assertOk()
            ->assertJsonPath('data.contents.items.0.is_featured', true);

        $this->getJson('/api/v1/cms/public/latest')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/cms/public/popular')
            ->assertOk()
            ->assertJsonPath('data.contents.items.0.title', 'Enterprise Guide');
    }

    public function test_public_category_and_tag_content_endpoints(): void
    {
        $this->getJson('/api/v1/cms/public/categories')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/cms/public/categories/guides/contents')
            ->assertOk()
            ->assertJsonPath('data.contents.items.0.slug', 'enterprise-guide');

        $this->getJson('/api/v1/cms/public/tags/enterprise/contents')
            ->assertOk()
            ->assertJsonPath('data.contents.items.0.slug', 'enterprise-guide');
    }

    public function test_private_api_requires_auth_and_can_preview_drafts(): void
    {
        $this->getJson('/api/v1/cms/private/contents/'.$this->draft->uuid)
            ->assertUnauthorized();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/cms/private/contents/'.$this->draft->uuid.'/preview')
            ->assertOk()
            ->assertJsonPath('data.content.title', 'Secret Draft')
            ->assertJsonStructure(['data' => ['content', 'seo']]);
    }

    public function test_cms_api_key_can_access_private_api(): void
    {
        $result = app(\App\Domains\Content\Services\CmsApiKeyService::class)->create([
            'name' => 'Headless consumer',
        ], $this->admin);

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
            'X-CMS-Api-Key' => $result['plain_text'],
        ])->getJson('/api/v1/cms/private/contents/'.$this->draft->uuid)
            ->assertOk()
            ->assertJsonPath('data.content.title', 'Secret Draft');

        $this->assertDatabaseHas('cms_api_keys', [
            'uuid' => $result['key']->uuid,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_manage_cms_api_keys(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/content/api-keys', [
            'name' => 'Dashboard key',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.api_key.name', 'Dashboard key');

        $uuid = $create->json('data.api_key.uuid');

        $this->getJson('/api/v1/content/api-keys')
            ->assertOk()
            ->assertJsonPath('data.api_keys.items.0.uuid', $uuid);

        $this->deleteJson('/api/v1/content/api-keys/'.$uuid)
            ->assertOk();

        $this->assertSoftDeleted('cms_api_keys', ['uuid' => $uuid]);
    }

    public function test_sitemap_and_robots_endpoints(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('enterprise-guide', false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('User-agent: *', false)
            ->assertSee('Sitemap:', false);

        $this->getJson('/api/v1/cms/seo/sitemap.json')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/cms/seo/robots.json')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
