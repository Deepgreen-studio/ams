<?php

namespace Tests\Feature\Content;

use App\Domains\Content\Enums\ContentBodyFormat;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentEditorTest extends TestCase
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
            'email' => 'editor-admin@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::Active,
            'is_active' => true,
        ]);
        $this->admin->assignRole('super-admin');

        $this->pageType = ContentType::query()->where('slug', ContentTypeSlug::Page->value)->firstOrFail();
        $this->draftStatus = ContentStatus::query()->where('slug', ContentStatusSlug::Draft->value)->firstOrFail();
    }

    public function test_admin_can_create_content_with_editor_fields(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/content', [
            'content_type_id' => $this->pageType->uuid,
            'title' => 'Editor Page',
            'summary' => 'Summary text',
            'excerpt' => 'Excerpt text',
            'body' => '<p>Hello <strong>world</strong></p>',
            'body_format' => ContentBodyFormat::Rich->value,
            'editor_json' => ['type' => 'doc', 'content' => []],
            'seo_title' => 'SEO Title',
            'seo_description' => 'SEO Description',
            'seo_keywords' => 'cms,editor',
            'canonical_url' => 'https://example.com/editor-page',
            'status' => 'draft',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.content.summary', 'Summary text')
            ->assertJsonPath('data.content.canonical_url', 'https://example.com/editor-page')
            ->assertJsonPath('data.content.body_format', 'rich');
    }

    public function test_admin_can_autosave_draft_quietly(): void
    {
        Sanctum::actingAs($this->admin);

        $content = Content::factory()->create([
            'content_type_id' => $this->pageType->id,
            'content_status_id' => $this->draftStatus->id,
            'title' => 'Autosave Me',
        ]);

        $response = $this->postJson('/api/v1/content/'.$content->uuid.'/autosave', [
            'title' => 'Autosaved Title',
            'summary' => 'Autosaved summary',
            'body' => '<p>Autosaved body</p>',
            'body_format' => 'html',
            'canonical_url' => 'https://example.com/autosaved',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content.title', 'Autosaved Title')
            ->assertJsonPath('data.content.body_format', 'html');

        $this->assertNotNull($response->json('data.last_autosaved_at'));
        $this->assertDatabaseHas('contents', [
            'uuid' => $content->uuid,
            'title' => 'Autosaved Title',
            'summary' => 'Autosaved summary',
        ]);
    }

    public function test_admin_can_upload_content_media_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->image('hero.png', 640, 480);

        $response = $this->post('/api/v1/content/media', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'media' => ['url', 'path', 'filename', 'mime_type'],
                ],
            ]);
    }

    public function test_publish_and_unpublish_from_editor_workflow(): void
    {
        Sanctum::actingAs($this->admin);

        $content = Content::factory()->create([
            'content_type_id' => $this->pageType->id,
            'content_status_id' => $this->draftStatus->id,
            'title' => 'Publish Flow',
            'body' => '<p>Ready</p>',
            'body_format' => 'rich',
        ]);

        $this->postJson('/api/v1/content/'.$content->uuid.'/workflow/submit')->assertOk();
        $this->postJson('/api/v1/content/'.$content->uuid.'/workflow/review')->assertOk();
        $this->postJson('/api/v1/content/'.$content->uuid.'/workflow/approve')->assertOk();
        $this->postJson('/api/v1/content/'.$content->uuid.'/workflow/publish')
            ->assertOk()
            ->assertJsonPath('data.content.status.slug', 'published');

        $this->postJson('/api/v1/content/'.$content->uuid.'/unpublish')
            ->assertOk()
            ->assertJsonPath('data.content.status.slug', 'draft');
    }
}
