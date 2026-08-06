<?php

namespace Tests\Feature\Support;

use App\Domains\Content\Models\Content;
use App\Domains\Support\Models\KnowledgeArticle;
use App\Models\User;
use Database\Seeders\ContentFoundationSeeder;
use Database\Seeders\KnowledgeBaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ContentFoundationSeeder::class);
        $this->seed(KnowledgeBaseSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'kb-admin@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_can_create_publish_and_view_article_types(): void
    {
        Sanctum::actingAs($this->admin);

        $categoryUuid = $this->getJson('/api/v1/support/knowledge/categories')
            ->assertOk()
            ->json('data.categories.0.uuid');

        $create = $this->postJson('/api/v1/support/knowledge/articles', [
            'title' => 'How to reset your password',
            'type' => 'faq',
            'summary' => 'Steps to reset password',
            'body' => '<p>Use Forgot Password on the login screen.</p>',
            'category_id' => $categoryUuid,
            'tags' => ['setup', 'security'],
            'is_featured' => true,
        ])->assertCreated();

        $uuid = $create->json('data.article.uuid');
        $this->assertSame('faq', $create->json('data.article.type'));

        $this->postJson('/api/v1/support/knowledge/articles/'.$uuid.'/publish')
            ->assertOk()
            ->assertJsonPath('data.article.status', 'published');

        $this->getJson('/api/v1/support/knowledge/articles/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.article.title', 'How to reset your password')
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/support/knowledge/articles?search=password&type=faq')
            ->assertOk()
            ->assertJsonPath('data.articles.meta.total', 1);
    }

    public function test_article_can_link_to_cms_content(): void
    {
        Sanctum::actingAs($this->admin);

        $content = Content::factory()->create([
            'title' => 'CMS Help Article',
            'slug' => 'cms-help-article',
            'summary' => 'From CMS',
            'body' => '<p>Body from CMS module</p>',
            'body_format' => 'html',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $article = $this->postJson('/api/v1/support/knowledge/articles', [
            'title' => 'Placeholder',
            'type' => 'guide',
            'body' => '<p>Local body</p>',
        ])->assertCreated()->json('data.article');

        $this->postJson('/api/v1/support/knowledge/articles/'.$article['uuid'].'/link-cms', [
            'content_id' => $content->uuid,
            'sync' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.article.title', 'CMS Help Article')
            ->assertJsonPath('data.article.content.uuid', $content->uuid)
            ->assertJsonPath('data.article.sync_from_cms', true);

        $this->assertDatabaseHas('knowledge_articles', [
            'uuid' => $article['uuid'],
            'content_id' => $content->id,
        ]);
    }

    public function test_version_history_and_restore(): void
    {
        Sanctum::actingAs($this->admin);

        $article = $this->postJson('/api/v1/support/knowledge/articles', [
            'title' => 'Versioned Guide',
            'type' => 'tutorial',
            'body' => '<p>Version 1</p>',
        ])->assertCreated()->json('data.article');

        $this->putJson('/api/v1/support/knowledge/articles/'.$article['uuid'], [
            'body' => '<p>Version 2</p>',
            'version_reason' => 'Improved steps',
        ])->assertOk()->assertJsonPath('data.article.version', 2);

        $versions = $this->getJson('/api/v1/support/knowledge/articles/'.$article['uuid'].'/versions')
            ->assertOk()
            ->json('data.versions');

        $this->assertGreaterThanOrEqual(2, count($versions));
        $first = collect($versions)->firstWhere('version', 1);

        $this->postJson('/api/v1/support/knowledge/articles/'.$article['uuid'].'/versions/'.$first['uuid'].'/restore')
            ->assertOk()
            ->assertJsonPath('data.article.body', '<p>Version 1</p>');
    }

    public function test_helpful_feedback_and_related_articles(): void
    {
        Sanctum::actingAs($this->admin);

        $a = $this->postJson('/api/v1/support/knowledge/articles', [
            'title' => 'Primary Article',
            'type' => 'article',
            'body' => '<p>Primary</p>',
            'status' => 'published',
            'tags' => ['api'],
        ])->assertCreated()->json('data.article');

        $b = $this->postJson('/api/v1/support/knowledge/articles', [
            'title' => 'Related Article',
            'type' => 'article',
            'body' => '<p>Related</p>',
            'status' => 'published',
            'tags' => ['api'],
            'related_article_ids' => [$a['uuid']],
        ])->assertCreated()->json('data.article');

        $this->postJson('/api/v1/support/knowledge/articles/'.$a['uuid'].'/feedback', [
            'is_helpful' => true,
        ])->assertOk();

        $model = KnowledgeArticle::query()->where('uuid', $a['uuid'])->firstOrFail();
        $this->assertSame(1, $model->helpful_count);

        $this->getJson('/api/v1/support/knowledge/articles/'.$b['uuid'])
            ->assertOk()
            ->assertJsonPath('data.related.0.uuid', $a['uuid']);
    }

    public function test_knowledge_dashboard_returns_center_data(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/support/knowledge/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'statistics',
                    'types',
                    'featured',
                    'latest',
                    'popular',
                    'categories',
                ],
            ]);
    }
}
