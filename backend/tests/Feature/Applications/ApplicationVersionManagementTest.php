<?php

namespace Tests\Feature\Applications;

use App\Domains\Applications\Models\Application;
use App\Domains\Applications\Models\ApplicationVersion;
use App\Domains\Companies\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationVersionManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'version-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $company = Company::query()->create([
            'company_name' => 'Version Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->application = Application::query()->create([
            'company_id' => $company->id,
            'name' => 'Mobile Banking',
            'slug' => 'mobile-banking',
            'platform' => 'android',
            'category' => 'finance',
            'status' => 'active',
            'visibility' => 'private',
            'current_version' => null,
            'minimum_supported_version' => null,
        ]);
    }

    public function test_admin_can_create_and_list_versions(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/applications/'.$this->application->uuid.'/versions', [
            'version_number' => '1.2.0',
            'build_number' => '120',
            'status' => 'testing',
            'release_notes' => 'Added biometric login',
            'minimum_supported_version' => '1.0.0',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version.version_number', '1.2.0')
            ->assertJsonPath('data.version.major', 1)
            ->assertJsonPath('data.version.minor', 2)
            ->assertJsonPath('data.version.patch', 0)
            ->assertJsonPath('data.version.status', 'testing');

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/versions')
            ->assertOk()
            ->assertJsonPath('data.versions.meta.total', 1)
            ->assertJsonPath('data.application.uuid', $this->application->uuid);
    }

    public function test_rejects_invalid_semver(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/versions', [
            'version_number' => '1.2',
            'status' => 'draft',
        ])->assertStatus(422);
    }

    public function test_production_version_syncs_application_fields_and_demotes_previous(): void
    {
        Sanctum::actingAs($this->admin);

        $first = ApplicationVersion::query()->create([
            'application_id' => $this->application->id,
            'version_number' => '1.0.0',
            'major' => 1,
            'minor' => 0,
            'patch' => 0,
            'status' => 'production',
            'minimum_supported_version' => '1.0.0',
            'release_date' => now()->subDays(10),
        ]);

        $this->application->update([
            'current_version' => '1.0.0',
            'minimum_supported_version' => '1.0.0',
        ]);

        $create = $this->postJson('/api/v1/applications/'.$this->application->uuid.'/versions', [
            'version_number' => '2.0.0',
            'status' => 'production',
            'minimum_supported_version' => '1.5.0',
            'release_date' => now()->toISOString(),
            'release_notes' => 'Major redesign',
        ])->assertCreated();

        $this->assertSame('2.0.0', $create->json('data.version.version_number'));
        $this->application->refresh();
        $this->assertSame('2.0.0', $this->application->current_version);
        $this->assertSame('1.5.0', $this->application->minimum_supported_version);

        $first->refresh();
        $this->assertSame('deprecated', $first->status->value);
    }

    public function test_can_compare_versions(): void
    {
        Sanctum::actingAs($this->admin);

        $from = ApplicationVersion::query()->create([
            'application_id' => $this->application->id,
            'version_number' => '1.0.0',
            'major' => 1,
            'minor' => 0,
            'patch' => 0,
            'status' => 'deprecated',
            'build_number' => '10',
            'release_notes' => 'Initial',
        ]);

        $to = ApplicationVersion::query()->create([
            'application_id' => $this->application->id,
            'version_number' => '1.1.0',
            'major' => 1,
            'minor' => 1,
            'patch' => 0,
            'status' => 'production',
            'build_number' => '20',
            'release_notes' => 'Patch notes',
        ]);

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/versions/compare?from='.$from->uuid.'&to='.$to->uuid)
            ->assertOk()
            ->assertJsonPath('data.comparison.result', 'upgrade')
            ->assertJsonPath('data.comparison.semver_diff.minor', 1)
            ->assertJsonPath('data.from.version_number', '1.0.0')
            ->assertJsonPath('data.to.version_number', '1.1.0');
    }

    public function test_timeline_and_history_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        $kept = ApplicationVersion::query()->create([
            'application_id' => $this->application->id,
            'version_number' => '1.0.0',
            'major' => 1,
            'minor' => 0,
            'patch' => 0,
            'status' => 'production',
        ]);

        $deleted = ApplicationVersion::query()->create([
            'application_id' => $this->application->id,
            'version_number' => '0.9.0',
            'major' => 0,
            'minor' => 9,
            'patch' => 0,
            'status' => 'deprecated',
        ]);
        $deleted->delete();

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/versions/timeline')
            ->assertOk()
            ->assertJsonCount(1, 'data.timeline')
            ->assertJsonPath('data.timeline.0.uuid', $kept->uuid);

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/versions/history')
            ->assertOk()
            ->assertJsonCount(2, 'data.history');
    }

    public function test_duplicate_version_number_is_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        ApplicationVersion::query()->create([
            'application_id' => $this->application->id,
            'version_number' => '3.0.0',
            'major' => 3,
            'minor' => 0,
            'patch' => 0,
            'status' => 'draft',
        ]);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/versions', [
            'major' => 3,
            'minor' => 0,
            'patch' => 0,
        ])->assertStatus(422);
    }

    public function test_guest_cannot_access_versions(): void
    {
        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/versions')->assertUnauthorized();
    }
}
