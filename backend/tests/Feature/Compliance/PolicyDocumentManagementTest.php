<?php

namespace Tests\Feature\Compliance;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Models\PolicyApproval;
use App\Domains\Compliance\Models\PolicyDocument;
use App\Domains\Compliance\Models\PolicyVersion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PolicyDocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'policy-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Policy Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_guest_cannot_list_policies(): void
    {
        $this->getJson('/api/v1/compliance/policies')->assertUnauthorized();
    }

    public function test_create_update_creates_immutable_versions(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/compliance/policies', [
            'company_id' => $this->company->uuid,
            'title' => 'Privacy Policy',
            'policy_type' => 'privacy_policy',
            'body' => 'Version one body',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.policy.current_version', 1)
            ->assertJsonPath('data.policy.status', 'draft');

        $uuid = $create->json('data.policy.uuid');
        $this->assertStringStartsWith('POL-', $create->json('data.policy.policy_number'));
        $this->assertSame(1, PolicyVersion::query()->where('policy_id', $create->json('data.policy.id'))->count());

        $this->putJson('/api/v1/compliance/policies/'.$uuid, [
            'body' => 'Version two body',
            'change_summary' => 'Clarified retention language',
        ])
            ->assertOk()
            ->assertJsonPath('data.policy.current_version', 2)
            ->assertJsonPath('data.policy.body', 'Version two body');

        $this->assertSame(2, PolicyVersion::query()->count());
        $this->assertDatabaseHas('policy_versions', [
            'version' => 1,
            'body' => 'Version one body',
        ]);
        $this->assertDatabaseHas('policy_versions', [
            'version' => 2,
            'body' => 'Version two body',
        ]);
    }

    public function test_compare_restore_and_approval_workflow(): void
    {
        Sanctum::actingAs($this->admin);

        $policy = PolicyDocument::factory()->forCompany($this->company)->create([
            'body' => 'Original',
            'current_version' => 1,
        ]);
        PolicyVersion::factory()->forPolicy($policy)->create(['version' => 1, 'body' => 'Original']);

        $this->putJson('/api/v1/compliance/policies/'.$policy->uuid, [
            'body' => 'Updated body',
            'change_summary' => 'Second revision',
        ])->assertOk();

        $this->getJson('/api/v1/compliance/policies/'.$policy->uuid.'/versions/compare?from=1&to=2')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['from', 'to', 'comparison' => ['changes', 'changed_fields']]]);

        $restore = $this->postJson('/api/v1/compliance/policies/'.$policy->uuid.'/versions/1/restore', [
            'reason' => 'Rollback wording',
        ]);

        $restore->assertOk()
            ->assertJsonPath('data.policy.current_version', 3)
            ->assertJsonPath('data.policy.body', 'Original');

        $this->assertTrue(
            PolicyVersion::query()->where('policy_id', $policy->id)->where('is_restore', true)->exists()
        );

        $this->postJson('/api/v1/compliance/policies/'.$policy->uuid.'/submit', [
            'comments' => 'Ready for legal review',
        ])
            ->assertOk()
            ->assertJsonPath('data.policy.status', 'review');

        $approval = PolicyApproval::query()->where('policy_id', $policy->id)->where('status', 'pending')->firstOrFail();

        $this->postJson('/api/v1/compliance/policies/approvals/'.$approval->uuid.'/approve', [
            'comments' => 'Looks good',
        ])
            ->assertOk()
            ->assertJsonPath('data.policy.status', 'approved');

        $this->postJson('/api/v1/compliance/policies/'.$policy->uuid.'/publish')
            ->assertOk()
            ->assertJsonPath('data.policy.status', 'published');
    }

    public function test_dashboard_and_cms_versions_unlinked(): void
    {
        Sanctum::actingAs($this->admin);

        $policy = PolicyDocument::factory()->forCompany($this->company)->create();
        PolicyVersion::factory()->forPolicy($policy)->create();

        $this->getJson('/api/v1/compliance/policies/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['statistics', 'recent', 'approval_queue'],
            ]);

        $this->getJson('/api/v1/compliance/policies/'.$policy->uuid.'/cms-versions')
            ->assertOk()
            ->assertJsonPath('data.linked', false);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/compliance/policies')->assertForbidden();
    }
}
