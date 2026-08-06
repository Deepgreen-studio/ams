<?php

namespace Tests\Feature\Content;

use App\Domains\Content\Models\MediaFolder;
use App\Domains\Content\Models\MediaLibraryItem;
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

class ContentMediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ContentFoundationSeeder::class);

        Storage::fake('public');
        config(['filesystems.media_library_disk' => 'public']);

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'cms-media@example.com',
            'password' => Hash::make('Password@123'),
            'status' => UserStatus::Active,
            'is_active' => true,
        ]);
        $this->admin->assignRole('super-admin');
    }

    public function test_admin_can_manage_folders_upload_preview_replace_and_version_history(): void
    {
        Sanctum::actingAs($this->admin);

        $folder = $this->postJson('/api/v1/content/media-folders', [
            'name' => 'Brand Assets',
        ])->assertCreated()
            ->json('data.folder');

        $this->getJson('/api/v1/content/media-folders/tree')
            ->assertOk()
            ->assertJsonPath('success', true);

        $file = UploadedFile::fake()->image('hero.png', 640, 480);
        $upload = $this->post('/api/v1/content/media-library', [
            'files' => [$file],
            'folder' => $folder['uuid'],
            'alt_text' => 'Hero image',
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $mediaUuid = $upload->json('data.media.0.uuid');
        $this->assertNotEmpty($mediaUuid);
        $this->assertDatabaseHas('media_library', [
            'uuid' => $mediaUuid,
            'version' => 1,
            'is_current' => 1,
            'type' => 'image',
            'extension' => 'png',
        ]);

        $this->getJson('/api/v1/content/media-library?type=image&folder='.$folder['uuid'])
            ->assertOk()
            ->assertJsonPath('data.media.meta.total', 1);

        $this->getJson('/api/v1/content/media-library/'.$mediaUuid)
            ->assertOk()
            ->assertJsonPath('data.media.alt_text', 'Hero image');

        $replacement = UploadedFile::fake()->image('hero-v2.jpg', 800, 600);
        $replaced = $this->post('/api/v1/content/media-library/'.$mediaUuid.'/replace', [
            'file' => $replacement,
        ], [
            'Accept' => 'application/json',
        ])->assertOk();

        $currentUuid = $replaced->json('data.media.uuid');
        $this->assertSame(2, $replaced->json('data.media.version'));
        $this->assertDatabaseHas('media_library', [
            'uuid' => $mediaUuid,
            'is_current' => 0,
            'version' => 1,
        ]);
        $this->assertDatabaseHas('media_library', [
            'uuid' => $currentUuid,
            'is_current' => 1,
            'version' => 2,
        ]);

        $versions = $this->getJson('/api/v1/content/media-library/'.$currentUuid.'/versions')
            ->assertOk()
            ->json('data.versions');
        $this->assertCount(2, $versions);

        $v1 = collect($versions)->firstWhere('version', 1);
        $this->postJson('/api/v1/content/media-library/'.$currentUuid.'/versions/'.$v1['uuid'].'/restore')
            ->assertOk()
            ->assertJsonPath('data.media.version', 3);

        $this->assertDatabaseCount('media_library', 3);
        $this->assertSame(1, MediaLibraryItem::query()->where('is_current', true)->count());

        $this->get('/api/v1/content/media-library/'.$currentUuid.'/download')
            ->assertOk();

        $this->deleteJson('/api/v1/content/media-library/'.MediaLibraryItem::query()->where('is_current', true)->firstOrFail()->uuid)
            ->assertOk();
    }

    public function test_editor_image_upload_persists_to_media_library(): void
    {
        Sanctum::actingAs($this->admin);

        $file = UploadedFile::fake()->image('editor.jpg');
        $response = $this->post('/api/v1/content/media', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $this->assertNotEmpty($response->json('data.media.url'));
        $this->assertDatabaseCount('media_library', 1);
        $this->assertDatabaseHas('media_library', [
            'extension' => 'jpg',
            'type' => 'image',
            'is_current' => 1,
        ]);
    }

    public function test_folder_cannot_be_deleted_when_not_empty(): void
    {
        Sanctum::actingAs($this->admin);

        $folder = MediaFolder::query()->create([
            'name' => 'Docs',
            'slug' => 'docs',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->post('/api/v1/content/media-library', [
            'files' => [UploadedFile::fake()->create('notes.pdf', 120, 'application/pdf')],
            'folder' => $folder->uuid,
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $this->deleteJson('/api/v1/content/media-folders/'.$folder->uuid)
            ->assertStatus(422);
    }
}
