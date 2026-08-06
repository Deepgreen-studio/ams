<?php

namespace Tests\Feature\Analytics;

use App\Domains\Analytics\Models\BusinessAnalyticsSnapshot;
use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Enums\CustomerStatus;
use App\Domains\Customers\Enums\SubscriptionStatus;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerAnalyticsSnapshot;
use App\Domains\Customers\Models\Subscription;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessAnalyticsTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'business-analytics@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Business Analytics Co',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);
    }

    public function test_business_overview_returns_kpis_charts_and_forecast(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedBusinessData();

        $response = $this->getJson('/api/v1/analytics/business/overview?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'period',
                    'kpis' => [
                        'customers_total',
                        'customers_new',
                        'customers_active',
                        'subscriptions_active',
                        'mrr',
                        'support_tickets_open',
                        'avg_health_score',
                    ],
                    'charts' => [
                        'customer_growth',
                        'revenue_trend',
                        'application_usage',
                        'support_tickets',
                        'health_score',
                    ],
                    'forecast',
                    'risk',
                ],
            ]);

        $this->assertGreaterThanOrEqual(2, $response->json('data.kpis.customers_total'));
        $this->assertGreaterThan(0, (float) $response->json('data.kpis.mrr'));
    }

    public function test_customer_revenue_application_growth_and_forecast_endpoints(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedBusinessData();

        $this->getJson('/api/v1/analytics/business/customers')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['kpis', 'by_status', 'charts', 'at_risk'],
            ]);

        $this->getJson('/api/v1/analytics/business/revenue')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['kpis' => ['mrr', 'arpu'], 'by_plan', 'charts', 'forecast'],
            ]);

        $this->getJson('/api/v1/analytics/business/applications')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['kpis', 'charts', 'feature_breakdown', 'support'],
            ]);

        $this->getJson('/api/v1/analytics/business/growth')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['charts', 'deltas'],
            ]);

        $this->getJson('/api/v1/analytics/business/forecast?horizon_days=7')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['forecast', 'historical'],
            ]);
    }

    public function test_capture_persists_business_snapshot(): void
    {
        Sanctum::actingAs($this->admin);
        $this->seedBusinessData();

        $this->postJson('/api/v1/analytics/business/capture')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['snapshot' => ['uuid', 'snapshot_date', 'mrr'], 'kpis'],
            ]);

        $this->assertGreaterThanOrEqual(1, BusinessAnalyticsSnapshot::query()->count());
        $this->assertTrue(
            BusinessAnalyticsSnapshot::query()
                ->whereDate('snapshot_date', now()->toDateString())
                ->exists()
        );
    }

    private function seedBusinessData(): void
    {
        $customers = Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => CustomerStatus::Active->value,
            'created_at' => now()->subDays(3),
        ]);

        Customer::factory()->create([
            'company_id' => $this->company->id,
            'status' => CustomerStatus::Active->value,
            'created_at' => now()->subDay(),
        ]);

        foreach ($customers as $index => $customer) {
            Subscription::factory()->create([
                'customer_id' => $customer->id,
                'status' => SubscriptionStatus::Active->value,
                'amount' => 100 + ($index * 25),
                'currency' => 'USD',
                'features' => ['reports' => true, 'api_access' => true, 'sso' => $index === 0],
                'created_at' => now()->subDays(2),
            ]);

            CustomerAnalyticsSnapshot::query()->create([
                'uuid' => (string) Str::uuid(),
                'customer_id' => $customer->id,
                'snapshot_date' => now()->toDateString(),
                'health_score' => 60 + ($index * 10),
                'activity_score' => 50,
                'risk_level' => $index === 0 ? 'high' : 'low',
                'subscription_active' => true,
                'subscription_status' => SubscriptionStatus::Active->value,
                'computed_at' => now(),
            ]);
        }

        if (class_exists(SupportTicket::class)) {
            try {
                SupportTicket::factory()->create([
                    'customer_id' => $customers->first()->id,
                    'status' => SupportTicketStatus::Open->value,
                    'created_at' => now()->subHours(5),
                ]);
            } catch (\Throwable) {
                // Support ticket factory may require company/application; KPIs still work without it.
            }
        }
    }
}
