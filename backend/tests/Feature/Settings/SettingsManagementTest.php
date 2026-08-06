<?php

namespace Tests\Feature\Settings;

use App\Domains\Settings\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'settings-admin@example.com']);
        $this->admin->assignRole('super-admin');
        $this->seed(SystemSettingsSeeder::class);
    }

    public function test_guest_cannot_view_settings(): void
    {
        $this->getJson('/api/v1/settings')->assertUnauthorized();
    }

    public function test_admin_can_list_and_update_general_settings(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['settings' => ['general', 'email', 'storage']]]);

        $this->putJson('/api/v1/settings', [
            'app_name' => 'AMS Enterprise',
            'timezone' => 'UTC',
            'maintenance_mode' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.settings.app_name.value', 'AMS Enterprise');

        $this->assertDatabaseHas('configuration_logs', [
            'setting_key' => 'general.app_name',
            'changed_by' => $this->admin->id,
        ]);
    }

    public function test_email_settings_mask_smtp_password(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/settings/email', [
            'smtp_host' => 'smtp.mail.test',
            'smtp_port' => 587,
            'smtp_password' => 'secret-pass',
            'from_email' => 'noreply@mail.test',
        ])->assertOk();

        $this->getJson('/api/v1/settings/email')
            ->assertOk()
            ->assertJsonPath('data.settings.smtp_password.value', '********');
    }

    public function test_security_and_api_settings_validation(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/settings/security', [
            'password_min_length' => 2,
        ])->assertStatus(422);

        $this->putJson('/api/v1/settings/api', [
            'default_page_size' => 25,
            'enabled' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.settings.default_page_size.value', 25);
    }

    public function test_admin_can_manage_folders_and_media(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->admin);

        $folder = $this->postJson('/api/v1/folders', [
            'name' => 'Marketing Assets',
        ])->assertCreated()->json('data.folder');

        $upload = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('banner.png', 120, 80),
            'folder_id' => $folder['uuid'],
        ], ['Accept' => 'application/json']);

        $upload->assertCreated()->assertJsonPath('success', true);
        $mediaUuid = $upload->json('data.media.0.uuid');

        $this->getJson('/api/v1/media?folder='.$folder['uuid'])
            ->assertOk()
            ->assertJsonPath('data.media.meta.total', 1);

        $this->deleteJson('/api/v1/media/'.$mediaUuid)->assertOk();
        $this->deleteJson('/api/v1/folders/'.$folder['uuid'])->assertOk();
    }

    public function test_manager_without_update_cannot_change_settings(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/settings')->assertOk();
        $this->putJson('/api/v1/settings', ['app_name' => 'Nope'])->assertForbidden();
    }

    public function test_system_info_endpoint(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/settings/system-info')
            ->assertOk()
            ->assertJsonStructure(['data' => ['system' => ['php_version', 'laravel_version', 'timezone']]]);
    }

    public function test_defaults_are_seeded(): void
    {
        $this->assertTrue(SystemSetting::query()->where('group', 'general')->where('key', 'app_name')->exists());
        $this->assertTrue(SystemSetting::query()->where('group', 'storage')->where('key', 'max_upload_kb')->exists());
    }
}
