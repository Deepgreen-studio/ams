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

class ApplicationReleaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Application $application;

    private ApplicationVersion $version;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'release-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $company = Company::query()->create([
            'company_name' => 'Release Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->application = Application::query()->create([
            'company_id' => $company->id,
            'name' => 'Release App',
            'slug' => 'release-app',
            'platform' => 'android',
            'category' => 'business',
            'status' => 'active',
            'visibility' => 'private',
        ]);

        $this->version = ApplicationVersion::query()->create([
            'application_id' => $this->application->id,
            'version_number' => '2.1.0',
            'major' => 2,
            'minor' => 1,
            'patch' => 0,
            'status' => 'draft',
        ]);
    }

    public function test_can_plan_approve_and_deploy_release(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/applications/'.$this->application->uuid.'/releases', [
            'application_version_id' => $this->version->uuid,
            'name' => 'Spring Release',
            'release_type' => 'minor',
            'requires_approval' => true,
            'plan_summary' => 'Ship feature pack',
            'notes' => [
                ['title' => 'What\'s new', 'content' => 'New checkout flow', 'audience' => 'public'],
            ],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.release.version_label', '2.1.0')
            ->assertJsonPath('data.release.approval_status', 'pending')
            ->assertJsonPath('data.release.notes.0.title', 'What\'s new');

        $uuid = $create->json('data.release.uuid');

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/releases/'.$uuid.'/schedule', [
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ])
            ->assertOk()
            ->assertJsonPath('data.release.status', 'scheduled');

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/releases/'.$uuid.'/approve', [
            'approval_notes' => 'Looks good',
        ])
            ->assertOk()
            ->assertJsonPath('data.release.status', 'approved')
            ->assertJsonPath('data.release.approval_status', 'approved')
            ->assertJsonPath('data.release.approver.email', $this->admin->email);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/releases/'.$uuid.'/deploy', [])
            ->assertOk()
            ->assertJsonPath('data.release.status', 'deployed')
            ->assertJsonPath('data.release.rollback_status', 'none');
    }

    public function test_cannot_deploy_without_approval(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/applications/'.$this->application->uuid.'/releases', [
            'application_version_id' => $this->version->uuid,
            'name' => 'Blocked Release',
            'requires_approval' => true,
        ])->assertCreated();

        $uuid = $create->json('data.release.uuid');

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/releases/'.$uuid.'/deploy', [])
            ->assertStatus(422);
    }

    public function test_can_rollback_deployed_release(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/applications/'.$this->application->uuid.'/releases', [
            'application_version_id' => $this->version->uuid,
            'name' => 'Hotfix Release',
            'release_type' => 'hotfix',
            'requires_approval' => false,
        ])->assertCreated();

        $uuid = $create->json('data.release.uuid');

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/releases/'.$uuid.'/deploy', [])
            ->assertOk()
            ->assertJsonPath('data.release.status', 'deployed');

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/releases/'.$uuid.'/rollback', [
            'reason' => 'Critical regression',
            'create_rollback_release' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.release.status', 'rolled_back')
            ->assertJsonPath('data.release.rollback_status', 'completed');

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/releases/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.rolled_back', 1);
    }

    public function test_calendar_and_timeline_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/applications/'.$this->application->uuid.'/releases', [
            'application_version_id' => $this->version->uuid,
            'name' => 'Calendar Release',
            'requires_approval' => false,
            'scheduled_at' => now()->addDays(3)->toIso8601String(),
            'deployment_date' => now()->addDays(3)->toIso8601String(),
        ])->assertCreated();

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/releases/calendar?from='.now()->startOfMonth()->toDateString().'&to='.now()->endOfMonth()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.releases.0.name', 'Calendar Release');

        $this->getJson('/api/v1/applications/'.$this->application->uuid.'/releases/timeline')
            ->assertOk()
            ->assertJsonPath('data.releases.0.name', 'Calendar Release');
    }
}
