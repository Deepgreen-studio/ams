<?php

namespace Tests\Feature\Dashboard;

use App\Domains\Applications\Enums\ApplicationStatus;
use App\Domains\Applications\Models\Application;
use App\Domains\Companies\Models\Company;
use App\Domains\Customers\Models\Customer;
use App\Domains\Support\Enums\SupportTicketPriority;
use App\Domains\Support\Enums\SupportTicketStatus;
use App\Domains\Support\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);

        $this->admin = User::factory()->create(['email' => 'dashboard-admin@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_guest_cannot_view_dashboard(): void
    {
        $this->getJson('/api/v1/dashboard')
            ->assertUnauthorized();
    }

    public function test_admin_can_view_dashboard_overview(): void
    {
        Sanctum::actingAs($this->admin);

        $company = Company::query()->create([
            'company_name' => 'Dashboard Tenant',
            'status' => 'active',
            'timezone' => 'UTC',
            'language' => 'en',
            'currency' => 'USD',
        ]);

        Application::factory()
            ->forCompany($company)
            ->create([
                'name' => 'AMS Mobile',
                'status' => ApplicationStatus::Active->value,
                'created_by' => $this->admin->id,
            ]);

        Customer::factory()->create([
            'company_id' => $company->id,
        ]);

        SupportTicket::factory()
            ->forCompany($company)
            ->create([
                'subject' => 'Login crash',
                'status' => SupportTicketStatus::Open->value,
                'priority' => SupportTicketPriority::High->value,
                'assigned_to' => $this->admin->id,
            ]);

        $this->getJson('/api/v1/dashboard?days=30')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'period' => ['from', 'to', 'days'],
                    'metrics',
                    'application_summary' => ['items'],
                    'overall_progress' => ['percent_completed', 'total', 'by_status'],
                    'todays_tasks' => ['tabs', 'items'],
                    'team_workload' => ['people'],
                ],
            ])
            ->assertJsonPath('data.period.days', 30)
            ->assertJsonPath('data.overall_progress.total', 1);
    }
}
