<?php

namespace Tests\Feature\Scheduler;

use App\Domains\Scheduler\Enums\ScheduledJobHandler;
use App\Domains\Scheduler\Enums\ScheduledJobRunStatus;
use App\Domains\Scheduler\Enums\ScheduledJobType;
use App\Domains\Scheduler\Models\ScheduledJob;
use App\Domains\Scheduler\Models\ScheduledJobRun;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchedulerEngineTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'scheduler-admin@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_admin_can_create_and_list_scheduled_jobs(): void
    {
        Sanctum::actingAs($this->admin);

        $create = $this->postJson('/api/v1/scheduler/jobs', [
            'name' => 'Daily Report Job',
            'description' => 'Morning report',
            'job_type' => ScheduledJobType::Cron->value,
            'handler_key' => ScheduledJobHandler::DailyReport->value,
            'schedule_cron' => '0 6 * * *',
            'is_enabled' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.job.name', 'Daily Report Job');

        $this->getJson('/api/v1/scheduler/jobs')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('scheduled_jobs', [
            'name' => 'Daily Report Job',
            'handler_key' => ScheduledJobHandler::DailyReport->value,
        ]);
    }

    public function test_manual_run_creates_success_history(): void
    {
        Sanctum::actingAs($this->admin);

        $job = ScheduledJob::query()->create([
            'name' => 'Customer Reminder',
            'job_type' => ScheduledJobType::Cron->value,
            'handler_key' => ScheduledJobHandler::CustomerReminder->value,
            'schedule_cron' => '0 9 * * *',
            'timezone' => 'UTC',
            'is_enabled' => true,
            'queue_name' => 'default',
            'payload' => ['limit' => 10],
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'next_run_at' => now()->addHour(),
        ]);

        $this->postJson("/api/v1/scheduler/jobs/{$job->uuid}/run")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('scheduled_job_runs', [
            'scheduled_job_id' => $job->id,
            'status' => ScheduledJobRunStatus::Success->value,
            'trigger' => 'manual',
        ]);

        $this->assertTrue(
            ScheduledJobRun::query()->where('scheduled_job_id', $job->id)->whereHas('logs')->exists()
        );
    }

    public function test_due_jobs_are_processed_by_command(): void
    {
        $job = ScheduledJob::query()->create([
            'name' => 'Delete Expired',
            'job_type' => ScheduledJobType::Cron->value,
            'handler_key' => ScheduledJobHandler::DeleteExpiredData->value,
            'schedule_cron' => '30 3 * * *',
            'timezone' => 'UTC',
            'is_enabled' => true,
            'queue_name' => 'default',
            'payload' => ['retention_days' => 30],
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'next_run_at' => now()->subMinute(),
        ]);

        $this->artisan('scheduler:process')->assertSuccessful();

        $this->assertDatabaseHas('scheduled_job_runs', [
            'scheduled_job_id' => $job->id,
            'status' => ScheduledJobRunStatus::Success->value,
            'trigger' => 'schedule',
        ]);

        $job->refresh();
        $this->assertNotNull($job->next_run_at);
        $this->assertTrue($job->next_run_at->isFuture());
    }

    public function test_failed_run_can_be_retried(): void
    {
        Sanctum::actingAs($this->admin);

        $job = ScheduledJob::query()->create([
            'name' => 'Retryable Report',
            'job_type' => ScheduledJobType::Cron->value,
            'handler_key' => ScheduledJobHandler::DailyReport->value,
            'schedule_cron' => '0 6 * * *',
            'timezone' => 'UTC',
            'is_enabled' => true,
            'payload' => [],
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'next_run_at' => now()->addHour(),
        ]);

        $run = ScheduledJobRun::query()->create([
            'scheduled_job_id' => $job->id,
            'status' => ScheduledJobRunStatus::Failed->value,
            'trigger' => 'manual',
            'attempt' => 1,
            'error_message' => 'failed',
            'started_at' => now()->subMinute(),
            'finished_at' => now()->subMinute(),
        ]);

        $this->postJson("/api/v1/scheduler/runs/{$run->uuid}/retry")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(
            ScheduledJobRun::query()
                ->where('scheduled_job_id', $job->id)
                ->where('trigger', 'retry')
                ->where('status', ScheduledJobRunStatus::Success->value)
                ->exists()
        );
    }

    public function test_dashboard_history_failed_and_statistics_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/scheduler/dashboard')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/scheduler/history')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/scheduler/running')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/scheduler/failed')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/scheduler/logs')->assertOk()->assertJsonPath('success', true);
        $this->getJson('/api/v1/scheduler/statistics')->assertOk()->assertJsonPath('success', true);
    }
}
