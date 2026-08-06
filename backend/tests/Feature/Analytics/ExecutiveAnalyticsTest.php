<?php

namespace Tests\Feature\Analytics;

use App\Domains\Analytics\Models\ExecutiveAnalyticsSnapshot;
use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\SubscriptionStatus;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExecutiveAnalyticsTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'executive-analytics@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Executive Analytics Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_executive_overview_returns_kpis_scorecards_and_widgets(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedExecutiveData();

        $response = $this->getJson('/api/v1/analytics/executive/overview?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'ceo')
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'label',
                    'period',
                    'kpis' => [
                        'mrr',
                        'customers_total',
                        'customers_active',
                        'applications_total',
                        'support_sla_on_track',
                        'compliance_risk_score',
                        'system_health_score',
                        'business_score',
                    ],
                    'scorecards',
                    'performance',
                    'growth',
                    'forecast',
                    'trends' => ['monthly', 'quarterly', 'yearly'],
                    'widgets',
                    'charts',
                    'focus',
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, $response->json('data.kpis.customers_total'));
        $this->assertNotEmpty($response->json('data.scorecards'));
        $this->assertArrayHasKey('top_customers', $response->json('data.widgets'));
        $this->assertArrayHasKey('revenue', $response->json('data.widgets'));
    }

    public function test_role_dashboards_scorecards_trends_forecast_and_widgets(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedExecutiveData();

        foreach (['ceo', 'admin', 'operations', 'compliance', 'support', 'customer'] as $type) {
            $this->getJson('/api/v1/analytics/executive/'.$type)
                ->assertOk()
                ->assertJsonPath('data.type', $type)
                ->assertJsonStructure([
                    'data' => ['kpis', 'scorecards', 'widgets', 'charts', 'focus'],
                ]);
        }

        $this->getJson('/api/v1/analytics/executive/scorecards')
            ->assertOk()
            ->assertJsonStructure(['data' => ['scorecards', 'performance', 'kpis']]);

        $this->getJson('/api/v1/analytics/executive/trends?granularity=monthly')
            ->assertOk()
            ->assertJsonPath('data.granularity', 'monthly')
            ->assertJsonStructure(['data' => ['series', 'available']]);

        $this->getJson('/api/v1/analytics/executive/forecast')
            ->assertOk()
            ->assertJsonStructure(['data' => ['forecast', 'growth']]);

        $this->getJson('/api/v1/analytics/executive/widgets')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'widgets' => [
                        'top_customers',
                        'top_applications',
                        'revenue',
                        'support_sla',
                        'compliance_status',
                        'system_health',
                        'growth_metrics',
                    ],
                ],
            ]);
    }

    public function test_capture_persists_executive_snapshot(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedExecutiveData();

        $response = $this->postJson('/api/v1/analytics/executive/capture', [
            'company' => $this->company->uuid,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'snapshot' => ['uuid', 'snapshot_date', 'business_score', 'mrr'],
                    'kpis',
                    'scorecards',
                ],
            ]);

        $this->assertDatabaseHas('executive_analytics_snapshots', [
            'company_id' => $this->company->id,
        ]);
        $this->assertGreaterThan(0, ExecutiveAnalyticsSnapshot::query()->count());
    }

    private function seedExecutiveData(): void
    {
        $customers = Customer::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => CustomerStatus::Active->value,
            'created_at' => now()->subDays(3),
        ]);

        foreach ($customers as $index => $customer) {
            Subscription::factory()->create([
                'customer_id' => $customer->id,
                'status' => SubscriptionStatus::Active->value,
                'amount' => 500 + ($index * 700),
                'currency' => 'USD',
                'created_at' => now()->subDays(2),
            ]);
        }

        if (class_exists(Application::class)) {
            try {
                Application::factory()->create([
                    'company_id' => $this->company->id,
                    'name' => 'Executive App',
                ]);
            } catch (\Throwable) {
                // Application factory may require extra relations in some environments.
            }
        }
    }
}
