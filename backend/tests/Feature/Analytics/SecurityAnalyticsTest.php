<?php

namespace Tests\Feature\Analytics;

use App\Domains\Analytics\Models\SecurityAnalyticsSnapshot;
use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Enums\PrivacyRequestType;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Users\Models\UserLoginHistory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SecurityAnalyticsTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'security-analytics@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Security Analytics Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_security_overview_returns_kpis_charts_and_heatmap(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedSecurityData();

        $response = $this->getJson('/api/v1/analytics/security/overview?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'kpis' => [
                        'logins_success',
                        'logins_failed',
                        'permission_changes',
                        'role_changes',
                        'data_exports',
                        'data_deletions',
                        'gdpr_requests',
                        'security_events',
                        'api_key_uses',
                        'risk_score',
                    ],
                    'risk' => ['score', 'level', 'indicators'],
                    'charts' => [
                        'logins_success',
                        'logins_failed',
                        'permission_changes',
                        'role_changes',
                        'gdpr_requests',
                        'security_events',
                        'risk_score',
                    ],
                    'heatmap',
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, $response->json('data.kpis.logins_success'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.kpis.logins_failed'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.kpis.gdpr_requests'));
    }

    public function test_audit_security_timeline_risk_heatmap_and_export_endpoints(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedSecurityData();

        $this->getJson('/api/v1/analytics/security/audit')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['kpis', 'charts', 'recent_role_events', 'recent_audit_actions', 'heatmap'],
            ]);

        $this->getJson('/api/v1/analytics/security/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['kpis', 'risk', 'charts', 'failed_login_ips', 'api_keys'],
            ]);

        $this->getJson('/api/v1/analytics/security/timeline')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['items', 'meta'],
            ]);

        $this->assertNotEmpty($this->getJson('/api/v1/analytics/security/timeline')->json('data.items'));

        $this->getJson('/api/v1/analytics/security/risk')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['score', 'level', 'indicators', 'kpis'],
            ]);

        $this->getJson('/api/v1/analytics/security/heatmap')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['heatmap'],
            ]);

        $this->getJson('/api/v1/analytics/security/export')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['generated_at', 'overview', 'timeline', 'export_ready'],
            ]);
    }

    public function test_capture_persists_security_snapshot(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedSecurityData();

        $response = $this->postJson('/api/v1/analytics/security/capture', [
            'company' => $this->company->uuid,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'snapshot' => ['uuid', 'snapshot_date', 'risk_score', 'logins_failed'],
                    'kpis',
                ],
            ]);

        $this->assertDatabaseHas('security_analytics_snapshots', [
            'company_id' => $this->company->id,
        ]);
        $this->assertGreaterThan(0, SecurityAnalyticsSnapshot::query()->count());
    }

    public function test_failed_login_is_recorded_for_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'failed-login@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422);

        $this->assertDatabaseHas('user_login_histories', [
            'user_id' => $user->id,
            'status' => 'failed',
        ]);
    }

    private function seedSecurityData(): void
    {
        UserLoginHistory::query()->create([
            'user_id' => $this->admin->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'device' => 'Desktop',
            'platform' => 'Windows',
            'operating_system' => 'Windows',
            'browser' => 'Chrome',
            'status' => 'success',
            'session_id' => (string) \Illuminate\Support\Str::uuid(),
            'logged_in_at' => now()->subHours(2),
        ]);

        UserLoginHistory::query()->create([
            'user_id' => $this->admin->id,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0',
            'device' => 'Desktop',
            'platform' => 'Windows',
            'operating_system' => 'Windows',
            'browser' => 'Firefox',
            'status' => 'failed',
            'session_id' => (string) \Illuminate\Support\Str::uuid(),
            'logged_in_at' => now()->subHour(),
        ]);

        Activity::query()->create([
            'log_name' => 'roles',
            'description' => 'Role permissions updated',
            'subject_type' => User::class,
            'subject_id' => $this->admin->id,
            'causer_type' => User::class,
            'causer_id' => $this->admin->id,
            'properties' => ['event' => 'permission_synced'],
            'event' => 'updated',
        ]);

        Activity::query()->create([
            'log_name' => 'roles',
            'description' => 'User role assigned',
            'subject_type' => User::class,
            'subject_id' => $this->admin->id,
            'causer_type' => User::class,
            'causer_id' => $this->admin->id,
            'properties' => ['event' => 'user_role_assigned'],
            'event' => 'updated',
        ]);

        PrivacyRequest::factory()
            ->forCompany($this->company)
            ->create([
                'request_type' => PrivacyRequestType::DataExport->value,
                'created_by' => $this->admin->id,
            ]);

        PrivacyRequest::factory()
            ->forCompany($this->company)
            ->create([
                'request_type' => PrivacyRequestType::DataDeletion->value,
                'created_by' => $this->admin->id,
            ]);
    }
}
