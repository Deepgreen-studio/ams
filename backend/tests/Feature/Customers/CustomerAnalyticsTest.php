<?php

namespace Tests\Feature\Customers;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\CustomerAnalyticsSnapshot;
use App\Domains\Customers\Models\CustomerApplication;
use App\Domains\Customers\Models\CustomerTask;
use App\Domains\Customers\Models\Subscription;
use App\Domains\Applications\Models\Application;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

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
            'company_name' => 'Analytics Tenant',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::factory()->business()->forCompany($this->company)->create([
            'email' => 'analytics-buyer@example.com',
            'company_name' => 'Analytics Buyer',
        ]);
    }

    public function test_guest_cannot_view_analytics(): void
    {
        $this->getJson('/api/v1/customer-analytics/dashboard?customer='.$this->customer->uuid)
            ->assertUnauthorized();
    }

    public function test_admin_can_view_dashboard_health_trends_and_usage(): void
    {
        Sanctum::actingAs($this->admin);

        $application = Application::factory()->active()->forCompany($this->company)->create();
        CustomerApplication::factory()->forCustomer($this->customer)->create([
            'application_id' => $application->id,
            'status' => 'active',
        ]);

        Subscription::factory()->forCustomer($this->customer)->create([
            'status' => 'active',
            'payment_status' => 'paid',
            'plan_type' => 'monthly',
        ]);

        CustomerTask::factory()->forCustomer($this->customer)->create([
            'status' => 'open',
            'due_at' => now()->subDay(),
        ]);

        $from = now()->subDays(2)->toDateString();
        $to = now()->toDateString();

        $dashboard = $this->getJson(
            '/api/v1/customer-analytics/dashboard?customer='.$this->customer->uuid.'&from='.$from.'&to='.$to
        );

        $dashboard->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer.uuid', $this->customer->uuid)
            ->assertJsonStructure([
                'data' => [
                    'current' => [
                        'health_score',
                        'activity_score',
                        'risk_level',
                        'applications_active',
                        'subscription_status',
                    ],
                    'risk_indicators',
                    'usage_report',
                    'charts',
                    'growth',
                    'timeline',
                ],
            ]);

        $this->assertGreaterThan(0, $dashboard->json('data.current.health_score'));
        $this->assertDatabaseHas('customer_analytics_snapshots', [
            'customer_id' => $this->customer->id,
        ]);

        $this->getJson('/api/v1/customer-analytics/health?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonStructure(['data' => ['health_score', 'activity_score', 'risk_indicators']]);

        $this->getJson('/api/v1/customer-analytics/trends?customer='.$this->customer->uuid.'&from='.$from.'&to='.$to)
            ->assertOk()
            ->assertJsonStructure(['data' => ['charts', 'growth']]);

        $this->getJson('/api/v1/customer-analytics/usage?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.usage.applications_active', 1);
    }

    public function test_admin_can_refresh_analytics_snapshot(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/customer-analytics/refresh', [
            'customer' => $this->customer->uuid,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.snapshot.customer_id', $this->customer->id);

        $this->assertSame(1, CustomerAnalyticsSnapshot::query()->where('customer_id', $this->customer->id)->count());
    }
}
