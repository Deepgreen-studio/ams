<?php

namespace Tests\Feature\Compliance;

use App\Domains\Companies\Models\Company;
use App\Domains\Compliance\Models\ComplianceCase;
use App\Domains\Compliance\Models\PrivacyRequest;
use App\Domains\Compliance\Models\RiskRegister;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComplianceAnalyticsTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'analytics-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Analytics Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_guest_cannot_view_analytics_dashboard(): void
    {
        $this->getJson('/api/v1/compliance/analytics/dashboard')->assertUnauthorized();
    }

    public function test_dashboard_returns_kpis_and_trends(): void
    {
        Sanctum::actingAs($this->admin);

        PrivacyRequest::factory()->forCompany($this->company)->create([
            'status' => 'completed',
            'completed_at' => now()->subHours(12),
            'created_at' => now()->subDays(1),
        ]);
        ComplianceCase::factory()->forCompany($this->company)->create(['status' => 'open']);
        RiskRegister::factory()->forCompany($this->company)->create([
            'status' => 'mitigating',
            'risk_score' => 16,
        ]);

        $response = $this->getJson('/api/v1/compliance/analytics/dashboard?company='.$this->company->uuid);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'kpis' => [
                        'privacy_requests',
                        'average_resolution_hours',
                        'compliance_cases',
                        'risk_score',
                        'open_risks',
                        'closed_risks',
                        'policy_updates',
                        'consent_granted',
                        'data_breaches',
                        'audit_events',
                    ],
                    'trends' => ['labels', 'privacy_requests', 'compliance_cases'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, $response->json('data.kpis.privacy_requests'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.kpis.compliance_cases'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.kpis.open_risks'));
    }

    public function test_report_endpoints_and_exports(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/compliance/analytics/risks')->assertOk()
            ->assertJsonStructure(['data' => ['summary', 'by_level', 'trends', 'top_risks']]);

        $this->getJson('/api/v1/compliance/analytics/reports/gdpr')->assertOk()
            ->assertJsonStructure(['data' => ['privacy_requests', 'data_breaches', 'dpia']]);

        $this->getJson('/api/v1/compliance/analytics/reports/consent')->assertOk()
            ->assertJsonStructure(['data' => ['summary', 'by_status', 'by_source']]);

        $this->getJson('/api/v1/compliance/analytics/reports/audit')->assertOk()
            ->assertJsonStructure(['data' => ['summary', 'by_event', 'recent', 'trends']]);

        $this->get('/api/v1/compliance/analytics/export?format=csv&report=overview')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->get('/api/v1/compliance/analytics/export?format=excel&report=gdpr')
            ->assertOk();

        $this->getJson('/api/v1/compliance/analytics/export?format=pdf')
            ->assertStatus(422)
            ->assertJsonPath('data.pdf_ready', true);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/compliance/analytics/dashboard')->assertForbidden();
    }
}
