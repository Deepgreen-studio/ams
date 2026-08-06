<?php

namespace Tests\Feature\Audit;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Audit\Models\ErrorLog;
use App\Domains\Audit\Models\SystemEvent;
use App\Domains\Audit\Services\ActivityLogService;
use App\Domains\Audit\Services\AuditTrailService;
use App\Domains\Audit\Services\ErrorLogService;
use App\Domains\Audit\Services\SystemEventService;
use App\Domains\Users\Models\UserLoginHistory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditMonitoringTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'audit-admin@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_guest_cannot_view_activity_logs(): void
    {
        $this->getJson('/api/v1/activity-logs')->assertUnauthorized();
    }

    public function test_admin_can_list_activity_logs_and_export_csv(): void
    {
        Sanctum::actingAs($this->admin);

        app(ActivityLogService::class)->record(
            module: 'audit',
            description: 'Test activity recorded',
            actor: $this->admin,
            event: 'created'
        );

        $this->getJson('/api/v1/activity-logs?search=Test activity')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.activity_logs.meta.total', 1);

        $export = $this->get('/api/v1/activity-logs/export?format=csv');
        $export->assertOk();
        $this->assertStringContainsString('text/csv', (string) $export->headers->get('content-type'));
    }

    public function test_audit_trail_and_system_events_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        app(AuditTrailService::class)->record(
            module: 'users',
            action: 'updated',
            actor: $this->admin,
            before: ['status' => 'active'],
            after: ['status' => 'inactive'],
            reason: 'Suspended for review'
        );

        app(SystemEventService::class)->record('cache_cleared', 'settings', ['by' => 'test']);

        $this->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->assertJsonPath('data.audit_logs.meta.total', 1);

        $this->getJson('/api/v1/system-events')
            ->assertOk()
            ->assertJsonPath('data.system_events.meta.total', 1);

        $this->assertDatabaseHas('audit_logs', ['module' => 'users', 'action' => 'updated']);
        $this->assertDatabaseHas('system_events', ['event' => 'cache_cleared']);
    }

    public function test_login_creates_login_history_record(): void
    {
        $user = User::factory()->create([
            'email' => 'login-audit@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('Password@123'),
            'is_active' => true,
        ]);
        $user->assignRole('super-admin');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'login-audit@example.com',
            'password' => 'Password@123',
        ])->assertOk();

        $this->assertDatabaseHas('user_login_histories', [
            'user_id' => $user->id,
            'status' => 'success',
        ]);

        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/login-history?user_id='.$user->id)
            ->assertOk()
            ->assertJsonPath('data.login_history.meta.total', 1);
    }

    public function test_error_log_service_persists_exception(): void
    {
        Sanctum::actingAs($this->admin);

        app(ErrorLogService::class)->capture(
            new \RuntimeException('Simulated failure'),
            request()
        );

        $this->getJson('/api/v1/error-logs?search=Simulated')
            ->assertOk()
            ->assertJsonPath('data.error_logs.meta.total', 1);

        $this->assertInstanceOf(ErrorLog::class, ErrorLog::query()->first());
    }

    public function test_manager_without_export_cannot_export_activity_logs(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/activity-logs')->assertOk();
        $this->getJson('/api/v1/activity-logs/export')->assertForbidden();
    }

    public function test_filter_validation_rejects_invalid_date_range(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/activity-logs?date_from=2026-08-10&date_to=2026-08-01')
            ->assertStatus(422);
    }

    public function test_models_have_expected_records(): void
    {
        $this->assertSame(0, AuditLog::query()->count());
        $this->assertSame(0, SystemEvent::query()->count());
        $this->assertSame(0, UserLoginHistory::query()->count());
    }
}
