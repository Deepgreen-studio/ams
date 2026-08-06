<?php

namespace Tests\Feature\Customers;

use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Domains\Customers\Models\License;
use App\Domains\Customers\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionLicenseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'billing-admin@example.com']);
        $this->admin->assignRole('super-admin');

        $this->company = Company::query()->create([
            'company_name' => 'Billing Tenant',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::factory()->business()->forCompany($this->company)->create([
            'email' => 'subscriber@example.com',
            'company_name' => 'Subscriber Org',
        ]);
    }

    public function test_guest_cannot_access_subscriptions(): void
    {
        $this->getJson('/api/v1/customer-subscriptions')->assertUnauthorized();
        $this->getJson('/api/v1/customer-licenses')->assertUnauthorized();
    }

    public function test_admin_can_create_subscription_with_license_and_view_dashboard(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/customer-subscriptions', [
            'customer_id' => $this->customer->uuid,
            'plan_type' => 'monthly',
            'plan_name' => 'Growth Monthly',
            'amount' => 99.00,
            'currency' => 'USD',
            'features' => ['dashboard', 'api_access'],
            'issue_license' => true,
            'max_activations' => 10,
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subscription.plan_type', 'monthly')
            ->assertJsonPath('data.subscription.plan_name', 'Growth Monthly')
            ->assertJsonPath('data.subscription.payment_provider', 'manual')
            ->assertJsonPath('data.subscription.status', 'active');

        $uuid = $create->json('data.subscription.uuid');
        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'customer_id' => $this->customer->id,
            'plan_type' => 'monthly',
        ]);

        $this->assertDatabaseCount('licenses', 1);
        $this->assertNotNull(License::query()->first()?->license_key);

        $this->getJson('/api/v1/customer-subscriptions/dashboard?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subscriptions.meta.total', 1)
            ->assertJsonStructure([
                'data' => [
                    'statistics' => [
                        'total',
                        'active',
                        'trialing',
                        'cancelled',
                        'expired',
                        'renewal_due_soon',
                    ],
                    'renewal_reminders',
                ],
            ]);

        $this->getJson('/api/v1/customer-subscriptions/'.$uuid)
            ->assertOk()
            ->assertJsonPath('data.subscription.customer.uuid', $this->customer->uuid)
            ->assertJsonPath('data.subscription.licenses.0.status', 'active');
    }

    public function test_admin_can_cancel_subscription_and_revoke_license(): void
    {
        Sanctum::actingAs($this->admin);

        $subscription = Subscription::factory()->forCustomer($this->customer)->create([
            'plan_type' => 'yearly',
            'status' => 'active',
            'payment_status' => 'paid',
        ]);

        $license = License::factory()->create([
            'subscription_id' => $subscription->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/customer-subscriptions/'.$subscription->uuid.'/cancel', [
            'reason' => 'Customer requested cancellation',
        ])
            ->assertOk()
            ->assertJsonPath('data.subscription.status', 'cancelled');

        $this->postJson('/api/v1/customer-licenses/'.$license->uuid.'/revoke', [
            'reason' => 'Subscription cancelled',
        ])
            ->assertOk()
            ->assertJsonPath('data.license.status', 'revoked');

        $this->assertNotNull($license->fresh()->revoked_at);
    }

    public function test_admin_can_list_license_history_and_payment_statistics(): void
    {
        Sanctum::actingAs($this->admin);

        $subscription = Subscription::factory()->forCustomer($this->customer)->create([
            'plan_type' => 'enterprise',
            'status' => 'active',
            'payment_status' => 'pending',
            'renews_at' => now()->addDays(7),
            'expires_at' => now()->addDays(7),
            'renewal_reminder_days' => 14,
        ]);

        License::factory()->create([
            'subscription_id' => $subscription->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        $archived = License::factory()->create([
            'subscription_id' => $subscription->id,
            'customer_id' => $this->customer->id,
            'status' => 'expired',
        ]);
        $archived->delete();

        $this->getJson('/api/v1/customer-licenses?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.licenses.meta.total', 1)
            ->assertJsonStructure(['data' => ['statistics']]);

        $this->getJson('/api/v1/customer-licenses/history?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.history.meta.total', 2);

        $this->getJson('/api/v1/customer-subscriptions/statistics?customer='.$this->customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.statistics.total', 1)
            ->assertJsonPath('data.statistics.renewal_due_soon', 1);
    }

    public function test_trial_plan_defaults_to_trialing_and_not_required_payment(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/customer-subscriptions', [
            'customer_id' => $this->customer->uuid,
            'plan_type' => 'trial',
            'issue_license' => false,
        ])
            ->assertCreated()
            ->assertJsonPath('data.subscription.status', 'trialing')
            ->assertJsonPath('data.subscription.payment_status', 'not_required');

        $this->assertDatabaseCount('licenses', 0);
    }

    public function test_subscription_and_license_can_be_archived_and_restored(): void
    {
        Sanctum::actingAs($this->admin);

        $subscription = Subscription::factory()->forCustomer($this->customer)->create();
        $license = License::factory()->create([
            'subscription_id' => $subscription->id,
            'customer_id' => $this->customer->id,
        ]);

        $this->deleteJson('/api/v1/customer-licenses/'.$license->uuid)->assertOk();
        $this->assertSoftDeleted('licenses', ['id' => $license->id]);

        $this->postJson('/api/v1/customer-licenses/'.$license->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.license.uuid', $license->uuid);

        $this->deleteJson('/api/v1/customer-subscriptions/'.$subscription->uuid)->assertOk();
        $this->assertSoftDeleted('subscriptions', ['id' => $subscription->id]);

        $this->postJson('/api/v1/customer-subscriptions/'.$subscription->uuid.'/restore')
            ->assertOk()
            ->assertJsonPath('data.subscription.uuid', $subscription->uuid);
    }
}
