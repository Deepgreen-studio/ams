<?php

namespace Tests\Feature\Compliance;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Models\DataBreach;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DataBreachManagementTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'breach-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Breach Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_guest_cannot_list_breaches(): void
    {
        $this->getJson('/api/v1/compliance/breaches')->assertUnauthorized();
    }

    public function test_admin_can_report_and_list_breach(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/compliance/breaches', [
            'company_id' => $this->company->uuid,
            'title' => 'Unauthorized database access',
            'description' => 'Suspected credential compromise',
            'breach_type' => 'unauthorized_access',
            'severity' => 'high',
            'personal_data_involved' => true,
            'affected_users' => [
                ['email' => 'victim@example.com', 'name' => 'Victim User'],
            ],
            'affected_data_categories' => ['email', 'password_hash'],
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.breach.title', 'Unauthorized database access')
            ->assertJsonPath('data.breach.status', 'reported')
            ->assertJsonPath('data.breach.affected_user_count', 1)
            ->assertJsonPath('data.breach.regulator_notification_required', true);

        $uuid = $create->json('data.breach.uuid');
        $this->assertStringStartsWith('BRH-', $create->json('data.breach.breach_number'));

        $this->getJson('/api/v1/compliance/breaches?search=Unauthorized')
            ->assertOk()
            ->assertJsonPath('data.breaches.meta.total', 1);

        $this->getJson('/api/v1/compliance/breaches/'.$uuid.'/timeline')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_risk_assessment_containment_and_notifications(): void
    {
        Sanctum::actingAs($this->admin);

        $breach = DataBreach::factory()->forCompany($this->company)->create([
            'status' => 'reported',
            'severity' => 'medium',
            'personal_data_involved' => true,
        ]);

        $this->postJson('/api/v1/compliance/breaches/'.$breach->uuid.'/assess', [
            'risk_likelihood' => 4,
            'risk_impact' => 5,
            'risk_assessment_notes' => 'Likely large exposure',
            'impact_analysis' => 'Customer PII potentially exposed',
        ])
            ->assertOk()
            ->assertJsonPath('data.breach.status', 'assessing')
            ->assertJsonPath('data.breach.risk_score', 20)
            ->assertJsonPath('data.breach.risk_level', 'critical');

        $this->postJson('/api/v1/compliance/breaches/'.$breach->uuid.'/contain', [
            'containment_summary' => 'Rotated credentials and blocked IP ranges',
        ])
            ->assertOk()
            ->assertJsonPath('data.breach.status', 'contained');

        $notification = $this->postJson('/api/v1/compliance/breaches/'.$breach->uuid.'/notifications', [
            'notification_type' => 'regulator',
            'channel' => 'email',
            'recipient' => 'ico@example.com',
            'subject' => 'Article 33 notification',
            'message' => 'Personal data breach notification',
            'send_now' => true,
            'regulator_reference' => 'ICO-123',
        ]);

        $notification->assertCreated()
            ->assertJsonPath('data.notification.status', 'sent');

        $this->assertNotNull($breach->fresh()->regulator_notified_at);
        $this->assertSame('ICO-123', $breach->fresh()->regulator_reference);

        $this->postJson('/api/v1/compliance/breaches/'.$breach->uuid.'/recover', [
            'recovery_summary' => 'Systems restored from clean backups',
        ])->assertOk();

        $this->postJson('/api/v1/compliance/breaches/'.$breach->uuid.'/root-cause', [
            'root_cause' => 'Weak MFA enforcement on admin accounts',
        ])->assertOk();

        $this->postJson('/api/v1/compliance/breaches/'.$breach->uuid.'/lessons-learned', [
            'lessons_learned' => 'Enforce phishing-resistant MFA organization-wide',
        ])->assertOk();

        $this->postJson('/api/v1/compliance/breaches/'.$breach->uuid.'/close', [
            'comments' => 'Incident resolved',
        ])
            ->assertOk()
            ->assertJsonPath('data.breach.status', 'closed');
    }

    public function test_cannot_close_without_required_regulator_notification(): void
    {
        Sanctum::actingAs($this->admin);

        $breach = DataBreach::factory()->forCompany($this->company)->create([
            'status' => 'contained',
            'regulator_notification_required' => true,
            'regulator_notified_at' => null,
        ]);

        $this->postJson('/api/v1/compliance/breaches/'.$breach->uuid.'/close', [])
            ->assertStatus(422);
    }

    public function test_dashboard_risk_matrix_and_reports(): void
    {
        Sanctum::actingAs($this->admin);

        DataBreach::factory()->forCompany($this->company)->critical()->create([
            'status' => 'assessing',
            'risk_likelihood' => 5,
            'risk_impact' => 5,
            'risk_score' => 25,
            'risk_level' => 'critical',
        ]);

        $this->getJson('/api/v1/compliance/breaches/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'statistics' => ['total', 'active', 'critical', 'regulator_pending'],
                    'recent_active',
                    'regulator_queue',
                ],
            ]);

        $this->getJson('/api/v1/compliance/breaches/risk-matrix')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/compliance/breaches/reports')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/compliance/breaches/notifications')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_affected_users_can_be_updated(): void
    {
        Sanctum::actingAs($this->admin);

        $breach = DataBreach::factory()->forCompany($this->company)->create();

        $this->putJson('/api/v1/compliance/breaches/'.$breach->uuid.'/affected-users', [
            'affected_users' => [
                ['email' => 'a@example.com', 'name' => 'A'],
                ['email' => 'b@example.com', 'name' => 'B'],
            ],
            'affected_data_categories' => ['email', 'phone'],
        ])
            ->assertOk()
            ->assertJsonPath('data.breach.affected_user_count', 2);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/compliance/breaches')->assertForbidden();
    }
}
