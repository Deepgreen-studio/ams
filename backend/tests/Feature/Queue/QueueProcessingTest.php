<?php

namespace Tests\Feature\Queue;

use App\Domains\Queue\Jobs\ProcessNotificationJob;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QueueProcessingTest extends TestCase
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

        $this->admin = User::factory()->create(['email' => 'queue-admin@example.com']);
        $this->admin->assignRole('super-admin');
    }

    public function test_dashboard_and_statistics_are_available(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/queue/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'connection',
                    'worker_queues',
                    'queue_sizes',
                    'pending',
                    'failed_count',
                    'tracks',
                ],
            ]);

        $this->getJson('/api/v1/queue/statistics')
            ->assertOk()
            ->assertJsonPath('data.connection', config('queue.default'));
    }

    public function test_sample_notification_job_can_be_dispatched_with_delay_and_priority(): void
    {
        Sanctum::actingAs($this->admin);
        Queue::fake();

        $this->postJson('/api/v1/queue/sample', [
            'channel' => 'email',
            'priority' => 'high',
            'delay_seconds' => 5,
            'payload' => ['message' => 'hello'],
        ])->assertCreated()
            ->assertJsonPath('data.queue', 'high')
            ->assertJsonPath('data.delay_seconds', 5);

        Queue::assertPushed(ProcessNotificationJob::class, function (ProcessNotificationJob $job) {
            return $job->channel === 'email'
                && $job->queue === 'high'
                && $job->delay !== null;
        });

        $this->assertDatabaseHas('queue_job_tracks', [
            'type' => 'notification',
            'queue' => 'high',
            'status' => 'queued',
        ]);
    }

    public function test_failed_job_can_be_retried_and_forgotten(): void
    {
        Sanctum::actingAs($this->admin);

        $uuid = (string) \Illuminate\Support\Str::uuid();
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'notifications',
            'payload' => json_encode([
                'uuid' => $uuid,
                'displayName' => ProcessNotificationJob::class,
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'data' => ['commandName' => ProcessNotificationJob::class, 'command' => ''],
            ]),
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);

        $this->getJson('/api/v1/queue/failed')
            ->assertOk()
            ->assertJsonPath('data.failed.meta.total', 1);

        $this->postJson('/api/v1/queue/failed/'.$uuid.'/retry')
            ->assertOk()
            ->assertJsonPath('data.retried', true);

        DB::table('failed_jobs')->insert([
            'uuid' => $uuid.'-2',
            'connection' => 'database',
            'queue' => 'notifications',
            'payload' => json_encode(['displayName' => 'DemoJob']),
            'exception' => 'Another failure',
            'failed_at' => now(),
        ]);

        $this->deleteJson('/api/v1/queue/failed/'.$uuid.'-2')
            ->assertOk();

        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid.'-2']);
    }

    public function test_restart_signal_can_be_sent(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/queue/restart')
            ->assertOk()
            ->assertJsonPath('data.restarted', true);
    }

    public function test_running_and_tracks_endpoints(): void
    {
        Sanctum::actingAs($this->admin);

        DB::table('queue_job_tracks')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'job_class' => ProcessNotificationJob::class,
            'display_name' => 'ProcessNotificationJob',
            'queue' => 'notifications',
            'priority' => 'low',
            'type' => 'notification',
            'status' => 'running',
            'attempts' => 1,
            'max_tries' => 3,
            'delay_seconds' => 0,
            'queued_at' => now(),
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/queue/running')
            ->assertOk()
            ->assertJsonPath('data.tracks.meta.total', 1);

        $this->getJson('/api/v1/queue/tracks?type=notification')
            ->assertOk()
            ->assertJsonPath('data.tracks.meta.total', 1);
    }
}
